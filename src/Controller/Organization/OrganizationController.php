<?php

namespace App\Controller\Organization;

use App\Controller\FrontController;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationInvitation;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Form\Organization\OrganizationDatasType;
use App\Form\Organization\OrganizationInvitationSendType;
use App\Form\User\OrganizationChoiceType;
use App\Form\User\RegisterType;
use App\Repository\Organization\OrganizationInvitationRepository;
use App\Repository\Perimeter\PerimeterDataRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Service\Email\EmailService;
use App\Service\Notification\NotificationService;
use App\Service\Organization\OrganizationService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrganizationController extends FrontController
{
    #[Route('/comptes/structure/information/', name: 'app_organization_structure_information')]
    public function structureInformation(
        UserService $userService,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        PerimeterRepository $perimeterRepository,
        PerimeterDataRepository $perimeterDataRepository
    ): Response
    {
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure');
        
        $user = $userService->getUserLogged();
        $organization = $user->getDefaultOrganization();
        if ($user instanceof User && $organization instanceof Organization) {
            if ($organization->getPerimeter() && !$user->getPerimeter()) {
                $user->setPerimeter($organization->getPerimeter());
                $managerRegistry->getManager()->persist($user);
                $managerRegistry->getManager()->flush();
            }
        }

        // si on a des infos manquantes, on va voir si elles sont dans les autres tables
        if ($organization instanceof Organization) {
            if (!$organization->getSirenCode() || !$organization->getSiretCode() || !$organization->getApeCode() || $organization->getInseeCode()) {
                if ($organization->getPerimeter() && $organization->getOrganizationType()) {
                    if (!$organization->getSirenCode() && $organization->getPerimeter()->getSiren()) {
                        $organization->setSirenCode($organization->getPerimeter()->getSiren());
                    }
                    if (!$organization->getSiretCode() && $organization->getPerimeter()->getSiret()) {
                        $organization->setSiretCode($organization->getPerimeter()->getSiret());
                    }
                    if (!$organization->getInseeCode() && $organization->getPerimeter()->getInsee()) {
                        $organization->setInseeCode($organization->getPerimeter()->getInsee());
                    }
                    if (!$organization->getApeCode()) {
                        $perimeterDatas = $perimeterDataRepository->findBy([
                            'perimeter' => $organization->getPerimeter(),
                        ]);
                        foreach ($perimeterDatas as $perimeterData) {
                            if ($perimeterData->getProp() == 'ape_code') {
                                $organization->setApeCode($perimeterData->getValue());
                                break;
                            }
                        }
                    }
                }
            }
        }
        $form = $this->createForm(RegisterType::class, $user, ['onlyOrganization' => true]);
        if ($form->has('mlConsent')) {
            $form->remove('mlConsent');
        }
        if ($organization instanceof Organization) {
            $form->get('organizationType')->setData($organization->getOrganizationType());
            $form->get('organizationName')->setData($organization->getName());
            $form->get('intercommunalityType')->setData($organization->getIntercommunalityType());
            $form->get('address')->setData($organization->getAddress());
            $form->get('cityName')->setData($organization->getCityName());
            $form->get('zipCode')->setData($organization->getZipCode());
            $form->get('address')->setData($organization->getAddress());
            $form->get('cityName')->setData($organization->getCityName());
            $form->get('zipCode')->setData($organization->getZipCode());
            $form->get('sirenCode')->setData($organization->getSirenCode());
            $form->get('siretCode')->setData($organization->getSiretCode());
            $form->get('apeCode')->setData($organization->getApeCode());
            $form->get('inseeCode')->setData($organization->getInseeCode());
        }
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $organization = $user->getDefaultOrganization() ?? new Organization();

                try {
                    if ($form->get('organizationName')->getData() && $form->get('organizationType')->getData()) {
                        $organization->setName($form->get('organizationName')->getData());
                        $organization->setOrganizationType($form->get('organizationType')->getData());
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
                        $organization->setIntercommunalityType($form->get('intercommunalityType')->getData());
                        $organization->setAddress($form->get('address')->getData());
                        $organization->setCityName($form->get('cityName')->getData());
                        $organization->setZipCode($form->get('zipCode')->getData());
                        $organization->setSirenCode($form->get('sirenCode')->getData());
                        $organization->setSiretCode($form->get('siretCode')->getData());
                        $organization->setApeCode($form->get('apeCode')->getData());
                        $organization->setInseeCode($form->get('inseeCode')->getData());
                        
                        // si nouvelle organization
                        if (!$user->getDefaultOrganization()) {
                            $user->addOrganization($organization);
                        }
                    }
                } catch (\Exception $e) {
                }
                
    
                // sauvegarde
                $managerRegistry->getManager()->persist($user); 
                $managerRegistry->getManager()->flush();
    
                // message retour
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Vos modifications ont été enregistrées avec succès.'
                );
    
                // redirection
                return $this->redirectToRoute('app_organization_structure_information');
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Votre formulaire contient des erreurs.'
                );
            }
        }

        $params = [
            'form' => $form->createView(),
            'organization' => $organization,
        ];
        // if (!$organization) {
            // $params['hideMenu'] = true;
        // }
        return $this->render('organization/organization/structure_information.html.twig', $params);
    }

    #[Route('/comptes/structure/donnees-cles/', name: 'app_organization_donnees_cles')]
    public function donneesCles(
        RequestStack $requestStack
    ): Response
    {
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure',$this->generateUrl('app_organization_structure_information'));
        $this->breadcrumb->add('Données clés');

        $formOrganizationChoice = $this->createForm(OrganizationChoiceType::class);
        $formOrganizationChoice->handleRequest($requestStack->getCurrentRequest());
        if ($formOrganizationChoice->isSubmitted()) {
            if ($formOrganizationChoice->isValid()) {
                $organization = $formOrganizationChoice->get('organization')->getData();
                return $this->redirectToRoute('app_organization_donnees_cles_details', ['id' => $organization->getId()]);
            }
        }

        return $this->render('organization/organization/donnees_cles.html.twig', [
            'formOrganizationChoice' => $formOrganizationChoice,
        ]);
    }

    #[Route('/comptes/structure/donnees-cles/{id}', name: 'app_organization_donnees_cles_details', requirements: ['id' => '[0-9]+'])]
    public function donneesClesDetails(
        UserService $userService,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        OrganizationService $organizationService
    ): Response
    {
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure',$this->generateUrl('app_organization_structure_information'));
        $this->breadcrumb->add('Données clés');
        
        $user = $userService->getUserLogged();
        $organization = $managerRegistry->getRepository(Organization::class)->find($requestStack->getCurrentRequest()->get('id'));
        if (!$organizationService->canEdit($user, $organization)) {
            return $this->redirectToRoute('app_organization_donnees_cles');
        }

        
        $formOrganizationDatas = $this->createForm(OrganizationDatasType::class, $organization);
        $formOrganizationDatas->handleRequest($requestStack->getCurrentRequest());
        if ($formOrganizationDatas->isSubmitted()) {
            if ($formOrganizationDatas->isValid()) {
                $managerRegistry->getManager()->persist($organization); 
                $managerRegistry->getManager()->flush();
                $this->addFlash(FrontController::FLASH_SUCCESS, 'Vos modifications ont été enregistrées avec succès.');
                return $this->redirectToRoute('app_organization_donnees_cles_details', ['id' => $organization->getId()]);
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Votre formulaire contient des erreurs.');
            }
        }

        return $this->render('organization/organization/donnees_cles_details.html.twig', [
            'form' => $formOrganizationDatas,
            'organization' => $organization
        ]);
    }

    #[Route('/comptes/structure/collaborateurs/', name: 'app_organization_collaborateurs')]
    public function Collaborateurs(
        UserService $userService, 
        ManagerRegistry $managerRegistry, 
        RequestStack $requestStack,
        EmailService $emailService,
        OrganizationInvitationRepository $organizationInvitationRepository
        ): Response
    {
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure',$this->generateUrl('app_organization_structure_information'));
        $this->breadcrumb->add('Collaborateurs');
        
        $user = $userService->getUserLogged();
        $organization = $user->getDefaultOrganization();

        $organizationInvitation = new OrganizationInvitation();
        $formInvitation = $this->createForm(OrganizationInvitationSendType::class, $organizationInvitation);
        $formInvitation->handleRequest($requestStack->getCurrentRequest());
        if ($formInvitation->isSubmitted()) {
            if ($formInvitation->isValid()) {
                if ($formInvitation->has('organization')) {
                    $organization = $formInvitation->get('organization')->getData();
                }

                // vérifie que email pas déjà invité
                $organizationInvitationCheck = $organizationInvitationRepository->findOneBy([
                    'email' => $organizationInvitation->getEmail(),
                    'organization' => $organization
                ]);

                if ($organizationInvitationCheck instanceof OrganizationInvitation) {
                    $this->addFlash(
                        FrontController::FLASH_ERROR,
                        'Cette personne a déjà été invitée à rejoindre cette organization.'
                    );
                    return $this->redirectToRoute('app_organization_collaborateurs');
                }

                // verifie que la personne ne fait pas déjà partie de l'organization
                if ($organization->getBeneficiairies()->filter(function($beneficiary) use ($organizationInvitation) {
                    return $beneficiary->getEmail() === $organizationInvitation->getEmail();
                })->count()) {
                    $this->addFlash(
                        FrontController::FLASH_ERROR,
                        'Cette personne fait déjà partie de cette organization.'
                    );
                    return $this->redirectToRoute('app_organization_collaborateurs');
                }
                // sauvegarde
                $organizationInvitation->setAuthor($user);
                $organizationInvitation->setOrganization($organization);
                $managerRegistry->getManager()->persist($organizationInvitation);
                $managerRegistry->getManager()->flush();

                // envoi du mail
                $emailService->sendEmail(
                    $organizationInvitation->getEmail(),
                    'Invitation à collaborer sur Aides-territoires',
                    'emails/organization/organization_invitation_send.html.twig',
                    [
                        'organizationInvitation' => $organizationInvitation,
                    ]
                );

                // message retour
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Votre invitation a bien été envoyée.'
                );

                // redirection
                return $this->redirectToRoute('app_organization_collaborateurs');
            } else {
                // message retour
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Votre formulaire d\'invitation contient des erreurs.'
                );
            }
        }

        $collaborators = [];
        $collaboratorsEmails = [];
        // les personnes déjà dans l'organization
        if ($organization) {
            foreach ($organization->getBeneficiairies() as $beneficiary) {
                $invitationId = null;
                $userInvitation = $organizationInvitationRepository->findOneBy([
                    'email' => $beneficiary->getEmail(),
                    'organization' => $organization
                ]);
                if ($userInvitation) {
                    $invitationId = $userInvitation->getId();
                }
                $excludable = false;
                $status = '';
                if ($userInvitation && $userInvitation->getTimeExclude()) {
                    $status = 'Exclu le '.$userInvitation->getTimeExclude()->format('d/m/Y');
                } elseif ($userInvitation && $userInvitation->getTimeAccept()) {
                    $status = 'Accepté le '.$userInvitation->getTimeAccept()->format('d/m/Y');
                    if ($userInvitation->getGuest() !== $user) {
                        $excludable = true;
                    }
                } elseif ($userInvitation && $userInvitation->getTimeRefuse()) {
                    $status = 'Refusé le '.$userInvitation->getTimeRefuse()->format('d/m/Y');
                }
                $collaborators[] = [
                    'name' => $beneficiary->getFirstname().' '.$beneficiary->getLastname(),
                    'email' => $beneficiary->getEmail(),
                    'role' => $beneficiary->getBeneficiaryRole() ?? '',
                    'dateInvite' => ($userInvitation && $userInvitation->getDateCreate()) ? $userInvitation->getDateCreate()->format('d/m/Y') : '',
                    'status' => $status,
                    'excludable' => $excludable,
                    'invitationId' => $invitationId,
                ];

                $collaboratorsEmails[] = $beneficiary->getEmail();
            }
        }

        $organizationInvitations = $organizationInvitationRepository->findBy([
            'organization' => $organization
        ]);

        /** @var OrganizationInvitation $organizationInvitation */
        foreach ($organizationInvitations as $organizationInvitation) {
            if (in_array($organizationInvitation->getEmail(), $collaboratorsEmails)) {
                continue;
            }
            $excludable = false;
            $status = '';
            if ($organizationInvitation->getGuest()) {
                if ($organizationInvitation && $organizationInvitation->getTimeExclude()) {
                    $status = 'Exclu le '.$organizationInvitation->getTimeExclude()->format('d/m/Y');
                } elseif ($organizationInvitation && $organizationInvitation->getTimeAccept()) {
                    $status = 'Accepté le '.$organizationInvitation->getTimeAccept()->format('d/m/Y');
                    $excludable = true;
                } elseif ($organizationInvitation && $organizationInvitation->getTimeRefuse()) {
                    $status = 'Refusé le '.$organizationInvitation->getTimeRefuse()->format('d/m/Y');
                }
            } else {
                $status = 'En attente';
            }
            $collaborators[] = [
                'name' => $organizationInvitation->getFirstname().' '.$organizationInvitation->getLastname(),
                'email' => $organizationInvitation->getEmail(),
                'role' => '',
                'dateInvite' => ($organizationInvitation->getDateCreate()) ? $organizationInvitation->getDateCreate()->format('d/m/Y') : '',
                'status' => $status,
                'excludable' => $excludable,
                'invitationId' => $organizationInvitation->getId(),
            ];
        }

        return $this->render('organization/organization/collaborateurs.html.twig', [
            'formInvitation' => $formInvitation,
            'collaborators' => $collaborators,
            'user' => $user,
        ]);
    }

    #[Route('/comptes/structure/invitations/', name: 'app_organization_invitations')]
    public function invitations(
        UserService $userService,
        OrganizationInvitationRepository $organizationInvitationRepository
    ): Response
    {
        $user = $userService->getUserLogged();

        $organizationInvitations = $organizationInvitationRepository->findBy([
            'email' => $user->getEmail(),
        ]);

        return $this->render('organization/organization/invitations.html.twig', [
            'organizationInvitations' => $organizationInvitations
        ]);
    }
    
    #[Route('/comptes/structure/invitations/{id}/accepter/', name: 'app_organization_invitations_accept')]
    public function acceptInvitation(
        $id,
        UserService $userService,
        OrganizationInvitationRepository $organizationInvitationRepository,
        ManagerRegistry $managerRegistry,
        NotificationService $notificationService
    ): Response
    {
        $user = $userService->getUserLogged();

        $organizationInvitation = $organizationInvitationRepository->find($id);
        if ($organizationInvitation->getEmail() !== $user->getEmail()) {
            return $this->redirectToRoute('app_organization_invitations');
        }

        // ajoute le user à l'organization
        $organization = $organizationInvitation->getOrganization();
        $organization->addBeneficiairy($user);
        $managerRegistry->getManager()->persist($organization);
        
        // met à jour l'invitation
        $organizationInvitation->setGuest($user);
        $organizationInvitation->setDateAccept(new \DateTime());
        $organizationInvitation->setTimeAccept(new \DateTime());
        $managerRegistry->getManager()->persist($organizationInvitation);
        $managerRegistry->getManager()->flush();

        // ajout notification au créateur de l'invitation
        $message = '
        <p>
            '.$user->getFirstname().' '.$user->getLastname().' a accepté votre invitation et vient de
            rejoindre votre structure '.$organization->getName().'.
        </p>
        ';
        $notificationService->addNotification($organizationInvitation->getAuthor(), 'Votre invitation a été acceptée', $message);

        // notification aux autres membres de l'organization
        foreach ($organization->getBeneficiairies() as $beneficiary) {
            // on ne notifie pas le user et l'auteur de l'invitation
            if (in_array($beneficiary->getId(), [$user->getId(), $organizationInvitation->getAuthor()->getId()])) {
                continue;
            }

            $message = '
            <p>
            '.$user->getFirstname().' '.$user->getLastname().' a accepté l’invitation de '.$organizationInvitation->getAuthor()->getFirstname().' '.$organizationInvitation->getAuthor()->getLastname().'
            et vient de rejoindre votre structure '.$organization->getName().'.
            </p>
            ';
            $notificationService->addNotification($beneficiary, 'Une invitation a été acceptée', $message);
        }
        // message
        $this->addFlash(
            FrontController::FLASH_SUCCESS,
            'Vous avez bien accepté l\'invitation.'
        );

        return $this->redirectToRoute('app_organization_invitations');
    }

    #[Route('/comptes/structure/invitations/{id}/refuser/', name: 'app_organization_invitations_refuse')]
    public function refuseInvitation(
        $id,
        UserService $userService,
        OrganizationInvitationRepository $organizationInvitationRepository,
        ManagerRegistry $managerRegistry,
        NotificationService $notificationService
    ): Response
    {
        $user = $userService->getUserLogged();

        $organizationInvitation = $organizationInvitationRepository->find($id);
        if ($organizationInvitation->getEmail() !== $user->getEmail()) {
            return $this->redirectToRoute('app_organization_invitations');
        }

        $organizationInvitation->setGuest($user);
        $organizationInvitation->setDateRefuse(new \DateTime());
        $organizationInvitation->setTimeRefuse(new \DateTime());
        $managerRegistry->getManager()->persist($organizationInvitation);
        $managerRegistry->getManager()->flush();

        // ajout notification au créateur de l'invitation
        $message = '
        <p>
            '.$user->getFirstname().' '.$user->getLastname().' a refusé votre invitation.
        </p>
        ';
        $notificationService->addNotification($organizationInvitation->getAuthor(), 'Votre invitation a été refusée', $message);

        // message
        $this->addFlash(
            FrontController::FLASH_SUCCESS,
            'Vous avez refusé l\'invitation.'
        );

        return $this->redirectToRoute('app_organization_invitations');
    }


    #[Route('/comptes/structure/invitations/{id}/exclure/', name: 'app_organization_invitations_exclude')]
    public function excludeInvitation(
        $id,
        UserService $userService,
        OrganizationInvitationRepository $organizationInvitationRepository,
        ManagerRegistry $managerRegistry,
        NotificationService $notificationService
    ): Response
    {
        $user = $userService->getUserLogged();

        // on vérifie que c'est bien l'auteur
        $organizationInvitation = $organizationInvitationRepository->find($id);
        if ($organizationInvitation->getAuthor() !== $user) {
            return $this->redirectToRoute('app_organization_invitations');
        }

        // ajout notification a l'utilisation de l'invitation
        $organizationName = $organizationInvitation->getOrganization() ? $organizationInvitation->getOrganization()->getName() : 'une structure';
        $message = '
        <p>
            '.$user->getFirstname().' '.$user->getLastname().' vous à exclu de l\'organization '.$organizationName.'.
        </p>
        ';

        if ($organizationInvitation->getGuest()) {
            $notificationService->addNotification($organizationInvitation->getGuest(), 'Vous avez été exclu de '.$organizationName, $message);
        }
        
        // message
        $this->addFlash(
            FrontController::FLASH_SUCCESS,
            'Vous avez exclu '.$organizationInvitation->getFirstname().' '.$organizationInvitation->getLastname().' de l\'organization.'
        );

        // retire l'organization à l'utilisateur
        $organizationInvitation->getOrganization()->removeBeneficiairy($organizationInvitation->getGuest());
        $managerRegistry->getManager()->persist($organizationInvitation->getOrganization());

        // l'exclusion
        $organizationInvitation->setTimeExclude(new \DateTime());
        $organizationInvitation->setGuest(null);
        $managerRegistry->getManager()->persist($organizationInvitation);
        
        // sauvegarde
        $managerRegistry->getManager()->flush();

        return $this->redirectToRoute('app_organization_collaborateurs');
    }

}
