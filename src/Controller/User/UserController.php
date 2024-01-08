<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationInvitation;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Form\User\RegisterType;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\User\NotificationRepository;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
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
        Security $security
    ): Response
    {
        // nouveau user
        $user = new User();

        // formulaire
        $formErrors = false;
        $formRegister = $this->createForm(RegisterType::class, $user);
        $formRegister->handleRequest($requestStack->getCurrentRequest());
        if ($formRegister->isSubmitted()) {
            if ($formRegister->isValid()) {
                $user->addRole(User::ROLE_USER);
                $user->setPassword($userPasswordHasherInterface->hashPassword($user, $formRegister->get('password')->getData()));

                // si infos organization
                if ($formRegister->get('organizationName')->getData() && $formRegister->get('organizationType')->getData()) {
                    $organization = new Organization();
                    $organization->setName($formRegister->get('organizationName')->getData());
                    $organization->setOrganizationType($formRegister->get('organizationType')->getData());
                    $organization->setPerimeter($user->getPerimeter());
                    $departementsCode = ($organization->getPerimeter()) ? $organization->getPerimeter()->getDepartments() : null;
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
                    $organization->setIntercommunalityType($formRegister->get('intercommunalityType')->getData());

                    $user->addOrganization($organization);
                }

                // sauvegarde le user et son organization
                $managerRegistry->getManager()->persist($user);
                $managerRegistry->getManager()->flush();

                // authentifie le user
                $security->login($user, 'form_login', 'main');

                // message success
                $this->tAddFlash(
                    FrontController::FLASH_SUCCESS,
                    'Vous êtes bien enregistré !'
                );

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
            } else {
                $formErrors = true;
            }
        }

        // rendu template
        return $this->render('user/user/register.html.twig', [
            'formRegister' => $formRegister->createView(),
            'no_breadcrumb' => true,
            'formErrors' => $formErrors
        ]);
    }
}
