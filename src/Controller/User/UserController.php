<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationInvitation;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Form\Security\ProConnectType;
use App\Form\User\RegisterCommuneType;
use App\Form\User\RegisterType;
use App\Repository\Organization\OrganizationTypeRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Service\Email\EmailService;
use App\Service\Matomo\MatomoService;
use App\Service\Security\SecurityService;
use App\Service\User\UserService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority: 5)]
class UserController extends FrontController
{
    #[Route('/comptes/inscription/', name: 'app_user_user_register')]
    public function register(
        RequestStack $requestStack,
        UserPasswordHasherInterface $userPasswordHasherInterface,
        PerimeterRepository $perimeterRepository,
        ManagerRegistry $managerRegistry,
        Security $security,
        MatomoService $matomoService,
        ParamService $paramService,
        EmailService $emailService
    ): Response {
        // nouveau user
        $user = new User();

        // formulaire
        $formErrors = false;
        $formRegister = $this->createForm(RegisterType::class, $user);
        $formRegister->handleRequest($requestStack->getCurrentRequest());
        if ($formRegister->isSubmitted()) {
            if ($formRegister->isValid()) {
                // Vérification manuelle de l'unicité de l'email, parfois l'UniqueEntity ne fonctionne pas
                $existingUser = $managerRegistry->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    $formRegister->get('email')->addError(new FormError('Cet email n\'est pas disponible.'));
                } else {
                    $user->addRole(User::ROLE_USER);
                    $user->setPassword(
                        $userPasswordHasherInterface->hashPassword(
                            $user,
                            $formRegister->get('password')->getData()
                        )
                    );

                    // si abonné newsletter
                    if ($user->isMlConsent()) {
                        // inscription à la newsletter
                        if ($emailService->subscribeUser($user)) {
                            $this->tAddFlash(
                                FrontController::FLASH_SUCCESS,
                                'Votre demande d’inscription à la newsletter a bien été prise en compte.<br />
                                <strong>Afin de finaliser votre inscription il vous reste à cliquer sur le lien
                                de confirmation présent dans l’e-mail que vous allez recevoir.</strong>'
                            );
                        } else {
                            // erreur Service, on notifie l'utilisateur
                            $this->tAddFlash(
                                FrontController::FLASH_ERROR,
                                'Une erreur s\'est produite lors de votre inscription à la newsletter'
                            );
                        }
                    }

                    // si infos organization
                    if (
                        $formRegister->get('organizationName')->getData()
                        && $formRegister->get('organizationType')->getData()
                    ) {
                        $organization = new Organization();
                        $organization->setName($formRegister->get('organizationName')->getData());
                        $organization->setOrganizationType($formRegister->get('organizationType')->getData());
                        $organization->setPerimeter($user->getPerimeter());
                        $departementsCode = ($organization->getPerimeter())
                            ? $organization->getPerimeter()->getDepartments()
                            : null
                        ;
                        $departementCode = $departementsCode[0] ?? null;
                        if ($departementCode) {
                            $departement = $perimeterRepository->findOneBy([
                                'code' => $departementCode,
                                'scale' => Perimeter::SCALE_COUNTY
                            ]);
                            if ($departement instanceof Perimeter) {
                                $organization->setPerimeterDepartment($departement);
                            }
                        }

                        $regionsCode = ($organization->getPerimeter())
                            ? $organization->getPerimeter()->getRegions()
                            : null
                        ;
                        $regionCode = $regionsCode[0] ?? null;
                        if ($regionCode) {
                            $region = $perimeterRepository->findOneBy([
                                'code' => $regionCode,
                                'scale' => Perimeter::SCALE_REGION
                            ]);
                            if ($region instanceof Perimeter) {
                                $organization->setPerimeterRegion($region);
                            }
                        }
                        $organization->setIntercommunalityType($formRegister->get('intercommunalityType')->getData());

                        $user->addOrganization($organization);
                    }

                    // sauvegarde le user et son organization
                    $managerRegistry->getManager()->persist($user);
                    $managerRegistry->getManager()->flush();

                    // authentifie le user
                    $security->login($user, SecurityService::DEFAULT_AUTHENTICATOR_NAME, SecurityService::DEFAULT_FIREWALL_NAME);

                    // message success
                    $this->tAddFlash(
                        FrontController::FLASH_SUCCESS,
                        'Vous êtes bien enregistré !'
                    );

                    // track goal
                    $matomoService->trackGoal((int) $paramService->get('goal_register_id'));

                    // regarde si il y a des invitations sur ce compte
                    $organizationInvitations = $managerRegistry->getRepository(OrganizationInvitation::class)->findBy([
                        'email' => $user->getEmail()
                    ]);
                    if (count($organizationInvitations) > 0) {
                        $urlRedirect = $this->generateUrl('app_organization_invitations');
                    }

                    // redirection
                    if (isset($urlRedirect)) {
                        return $this->redirect($urlRedirect);
                    } else {
                        return $this->redirectToRoute('app_user_dashboard');
                    }
                }
            } else {
                $formErrors = true;
            }
        }

        // formulaire proConnnect
        $formProConnect = $this->createForm(ProConnectType::class, null, ['action' => $this->generateUrl('app_login_proconnect')]);

        // rendu template
        return $this->render('user/user/register.html.twig', [
            'formRegister' => $formRegister,
            'formProConnect' => $formProConnect,
            'no_breadcrumb' => true,
            'formErrors' => $formErrors
        ]);
    }

    #[Route('/comptes/inscription-mairie/', name: 'app_user_user_register_commune')]
    public function registerCommune(
        UserService $userService,
        RequestStack $requestStack,
        OrganizationTypeRepository $organizationTypeRepository,
        PerimeterRepository $perimeterRepository,
        ManagerRegistry $managerRegistry,
        UserPasswordHasherInterface $userPasswordHasherInterface,
        Security $security,
        MatomoService $matomoService,
        ParamService $paramService
    ): Response {
        // le user
        $user = $userService->getUserLogged();

        // si déjà connecté on regidirige sur mon compte
        if ($user) {
            return $this->redirectToRoute('app_user_parameter_profil');
        }

        // nouveau user avec valeurs forcées
        $user = new User();
        $user->setIsBeneficiary(true);
        $user->setIsContributor(true);
        $user->setAcquisitionChannel(User::ACQUISITION_CHANNEL_ANIMATOR);
        $user->addRole(User::ROLE_USER);

        // nouvelle organization commune
        $organization = new Organization();
        $organization->setOrganizationType(
            $organizationTypeRepository->findOneBy(['slug' => OrganizationType::SLUG_COMMUNE])
        );
        $user->addOrganization($organization);

        // formulaire inscription
        $formRegisterCommune = $this->createForm(RegisterCommuneType::class, $user);
        $formRegisterCommune->handleRequest($requestStack->getCurrentRequest());
        if ($formRegisterCommune->isSubmitted()) {
            if ($formRegisterCommune->isValid()) {
                // Vérification manuelle de l'unicité de l'email, parfois l'UniqueEntity ne fonctionne pas
                $existingUser = $managerRegistry->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    $formRegisterCommune->get('email')->addError(new FormError('Cet email n\'est pas disponible.'));
                } else {
                    // encode le password
                    $user->setPassword(
                        $userPasswordHasherInterface->hashPassword(
                            $user,
                            $formRegisterCommune->get('password')->getData()
                        )
                    );

                    // assigne le perimetre à l'organisation
                    $organization->setPerimeter($user->getPerimeter());

                    // le nom de l'organization en fonction du perimetre
                    $organization->setName('Mairie de ' . $organization->getPerimeter()->getName());

                    // defini le departement de l'organisation
                    $departementsCode = ($organization->getPerimeter())
                        ? $organization->getPerimeter()->getDepartments()
                        : null
                    ;
                    $departementCode = $departementsCode[0] ?? null;
                    if ($departementCode) {
                        $departement = $perimeterRepository->findOneBy([
                            'code' => $departementCode,
                            'scale' => Perimeter::SCALE_COUNTY
                        ]);
                        if ($departement instanceof Perimeter) {
                            $organization->setPerimeterDepartment($departement);
                        }
                    }

                    // défini la région de l'organisation
                    $regionsCode = ($organization->getPerimeter()) ? $organization->getPerimeter()->getRegions() : null;
                    $regionCode = $regionsCode[0] ?? null;
                    if ($regionCode) {
                        $region = $perimeterRepository->findOneBy([
                            'code' => $regionCode,
                            'scale' => Perimeter::SCALE_REGION
                        ]);
                        if ($region instanceof Perimeter) {
                            $organization->setPerimeterRegion($region);
                        }
                    }

                    // on sauvegarde le nouveau user et on organization
                    $managerRegistry->getManager()->persist($user);
                    $managerRegistry->getManager()->flush();

                    // authentifie le user
                    $security->login($user, 'form_login', 'main');

                    // message success
                    $this->tAddFlash(
                        FrontController::FLASH_SUCCESS,
                        'Vous êtes bien enregistré !'
                    );

                    // track goal
                    $matomoService->trackGoal((int) $paramService->get('goal_register_id'));

                    // redirection
                    return $this->redirectToRoute('app_user_dashboard');
                }
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Impossible de valider le formulaire, veuillez verifier vos informations'
                );
            }
        }

        // formulaire proConnnect
        $formProConnect = $this->createForm(ProConnectType::class, null, ['action' => $this->generateUrl('app_login_proconnect')]);


        // rendu template
        return $this->render('user/user/register_commune.html.twig', [
            'formRegisterCommune' => $formRegisterCommune,
            'formProConnect' => $formProConnect
        ]);
    }
}
