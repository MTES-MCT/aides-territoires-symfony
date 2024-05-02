<?php

namespace App\Controller\Organization;

use App\Controller\FrontController;
use App\Entity\Backer\Backer;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationAccess;
use App\Entity\Organization\OrganizationInvitation;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Form\Backer\BackerEditType;
use App\Form\Organization\OrganizationAccessCollectionType;
use App\Form\Organization\OrganizationAccessEditTableType;
use App\Form\Organization\OrganizationDatasType;
use App\Form\Organization\OrganizationEditType;
use App\Form\Organization\OrganizationInvitationSendType;
use App\Form\User\OrganizationChoiceType;
use App\Repository\Backer\BackerRepository;
use App\Repository\Organization\OrganizationInvitationRepository;
use App\Repository\Organization\OrganizationRepository;
use App\Repository\Perimeter\PerimeterDataRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Service\Backer\BackerService;
use App\Service\Email\EmailService;
use App\Service\Image\ImageService;
use App\Service\Notification\NotificationService;
use App\Service\Organization\OrganizationService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrganizationController extends FrontController
{
    #[Route('/comptes/structure/information/{id?}', name: 'app_organization_structure_information', requirements: ['id' => '\d+'])]
    public function informationEdit(
        UserService $userService,
        ManagerRegistry $managerRegistry,
        OrganizationRepository $organizationRepository,
        OrganizationService $organizationService,
        RequestStack $requestStack,
        PerimeterRepository $perimeterRepository,
        PerimeterDataRepository $perimeterDataRepository,
        ?int $id = null
    ) : Response {
        // le user        
        $user = $userService->getUserLogged();

        // l'organization
        $organization = null;
        if ($id) {
            $organization = $organizationRepository->find($id);
        }
        if (!$organization) {
            $organization = new Organization();
        }

        // si organization on verifie que l'utilisateur peut éditer
        if ($organization->getId()) {
            if (!$organizationService->canViewEdit($user, $organization)) {
                return $this->redirectToRoute('app_user_dashboard');
            }

            // si le user n'as pas de périmètre on essaye de lui en attribuer un à partir de l'organization
            if ($organization->getPerimeter() && !$user->getPerimeter()) {
                $user->setPerimeter($organization->getPerimeter());
                $managerRegistry->getManager()->persist($user);
                $managerRegistry->getManager()->flush();
            }


            // si on a des infos manquantes, on va voir si elles sont dans les autres tables
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

        // regarde si le user est admin de l'organsation
        $userAdminOf = $organizationService->userAdminOf($user, $organization);

        // formulaire edition organization
        $formOptions = [
            'is_readonly' => !$userAdminOf
        ];
        $form = $this->createForm(OrganizationEditType::class, $organization, $formOptions);
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid() && $userAdminOf) {
                // essaye de déterminer le departement
                if ($organization->getPerimeter() && $organization->getPerimeter()->getDepartments()) {
                    $departementsCode = $organization->getPerimeter()->getDepartments() ;
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
                }

                // essaye de determiner la region
                if ($organization->getPerimeter() && $organization->getPerimeter()->getRegions()) {
                    $regionsCode = $organization->getPerimeter()->getRegions();
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
                }

                // si nouvelle organization
                if (!$user->getDefaultOrganization()) {
                    $organizationAccess = new OrganizationAccess();
                    $organizationAccess->setOrganization($organization);
                    $organizationAccess->setAdministrator(true);
                    $organizationAccess->setEditAid(true);
                    $organizationAccess->setEditPortal(true);
                    $organizationAccess->setEditBacker(true);
                    $organizationAccess->setEditProject(true);
                    $user->addOrganizationAccess($organizationAccess);
                    $managerRegistry->getManager()->persist($user);
                }

                // sauvegarde
                $managerRegistry->getManager()->persist($organization); 
                $managerRegistry->getManager()->flush();
    
                // message retour
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Vos modifications ont été enregistrées avec succès.'
                );
    
                // redirection
                return $this->redirectToRoute('app_organization_structure_information', ['id' => $organization->getId()]);
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Votre formulaire contient des erreurs.'
                );
            }
        }

        // fil arianne
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add($organization->getId() ? $organization->getName() : 'Nouvelle structure');

        // rendu template
        return $this->render('organization/organization/structure_information.html.twig', [
            'form' => $form,
            'organization' => $organization,
            'organization_edited_id' => $organization->getId(),
            'userAdminOf' => $userAdminOf
        ]);
    }
    #[Route('/comptes/structure/donnees-cles/', name: 'app_organization_donnees_cles')]
    public function donneesCles(
        RequestStack $requestStack
    ): Response
    {
        // page obsolète
        return $this->redirectToRoute('app_user_dashboard');
    }

    #[Route('/comptes/structure/donnees-cles/{id}', name: 'app_organization_donnees_cles_details', requirements: ['id' => '[0-9]+'])]
    public function donneesClesDetails(
        int $id,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        OrganizationService $organizationService
    ): Response
    {
        // le user
        $user = $userService->getUserLogged();
        // la structure
        $organization = $managerRegistry->getRepository(Organization::class)->find($id);
        if (!$organization instanceof Organization) {
            return $this->redirectToRoute('app_user_dashboard');
        }
        // on verifie que l'utilisateur peut éditer
        if (!$organizationService->canViewEdit($user, $organization)) {
            return $this->redirectToRoute('app_organization_donnees_cles');
        }

        // regarde si le user est admin de l'organsation
        $userAdminOf = $organizationService->userAdminOf($user, $organization);

        // formulaire
        $formOptions = [
            'is_readonly' => !$userAdminOf
        ];
        $formOrganizationDatas = $this->createForm(OrganizationDatasType::class, $organization, $formOptions);
        $formOrganizationDatas->handleRequest($requestStack->getCurrentRequest());
        if ($formOrganizationDatas->isSubmitted()) {
            if ($formOrganizationDatas->isValid() && $userAdminOf) {
                $managerRegistry->getManager()->persist($organization); 
                $managerRegistry->getManager()->flush();
                $this->addFlash(FrontController::FLASH_SUCCESS, 'Vos modifications ont été enregistrées avec succès.');
                return $this->redirectToRoute('app_organization_donnees_cles_details', ['id' => $organization->getId()]);
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Votre formulaire contient des erreurs.');
            }
        }

        // fil d'arianne
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure',$this->generateUrl('app_organization_structure_information'));
        $this->breadcrumb->add('Données clés');

        // rendu template
        return $this->render('organization/organization/donnees_cles_details.html.twig', [
            'form' => $formOrganizationDatas,
            'organization' => $organization,
            'userAdminOf' => $userAdminOf
        ]);
    }

    #[Route('/comptes/structure/collaborateurs/{id}', name: 'app_organization_collaborateurs', requirements: ['id' => '[0-9]+'])]
    public function collaborateurs(
        $id,
        UserService $userService, 
        ManagerRegistry $managerRegistry, 
        RequestStack $requestStack,
        EmailService $emailService,
        OrganizationInvitationRepository $organizationInvitationRepository,
        OrganizationRepository $organizationRepository,
        OrganizationService $organizationService
        ): Response
    {        
        $user = $userService->getUserLogged();
        $organization = $organizationRepository->find($id);

        // verifie l'organization
        if (!$organization instanceof Organization) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // verifie que l'utilisateur peut éditer
        if (!$organizationService->canViewEdit($user, $organization)) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // regarde si le user est admin de l'organisation
        $userAdminOf = $organizationService->userAdminOf($user, $organization);

        $formAccesses = $this->createForm(OrganizationAccessCollectionType::class, $organization);
        $formAccesses->handleRequest($requestStack->getCurrentRequest());
        if ($formAccesses->isSubmitted()) {
            if ($formAccesses->isValid() && $userAdminOf) {
                // vérifie qu'il reste au moins 1 admin dans le groupe
                if (!$organizationService->hasOneAdminAtLeast($organization)) {
                    $this->addFlash(FrontController::FLASH_ERROR, 'Il doit y avoir au moins un administrateur.');
                } else {
                    $managerRegistry->getManager()->persist($organization);
                    $managerRegistry->getManager()->flush();
                    $this->addFlash(FrontController::FLASH_SUCCESS, 'Vos modifications ont été enregistrées avec succès.');
                }
                return $this->redirectToRoute('app_organization_collaborateurs', ['id' => $organization->getId()]);
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Le formulaire contient des erreurs.');
            }
        }

        $organizationInvitation = new OrganizationInvitation();
        $formInvitation = $this->createForm(OrganizationInvitationSendType::class, $organizationInvitation);
        $formInvitation->handleRequest($requestStack->getCurrentRequest());
        if ($formInvitation->isSubmitted()) {
            if ($formInvitation->isValid() && $userAdminOf) {
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
                    return $this->redirectToRoute('app_organization_collaborateurs', ['id' => $organization->getId()]);
                }

                // verifie que la personne ne fait pas déjà partie de l'organization
                if ($organizationService->emailMemberOf($organizationInvitation->getEmail(), $organization)) {
                    $this->addFlash(
                        FrontController::FLASH_ERROR,
                        'Cette personne fait déjà partie de cette organization.'
                    );
                    return $this->redirectToRoute('app_organization_collaborateurs', ['id' => $organization->getId()]);
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
                return $this->redirectToRoute('app_organization_collaborateurs', ['id' => $organization->getId()]);
            } else {
                // message retour
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Votre formulaire d\'invitation contient des erreurs.'
                );
            }
        }

        foreach ($organization->getOrganizationInvitations() as $organizationInvitation) {
            $status = '';
            if ($organizationInvitation->getGuest()) {
                if ($organizationInvitation && $organizationInvitation->getTimeExclude()) {
                    $status = 'Exclu le '.$organizationInvitation->getTimeExclude()->format('d/m/Y');
                } elseif ($organizationInvitation && $organizationInvitation->getTimeAccept()) {
                    $status = 'Accepté le '.$organizationInvitation->getTimeAccept()->format('d/m/Y');
                } elseif ($organizationInvitation && $organizationInvitation->getTimeRefuse()) {
                    $status = 'Refusé le '.$organizationInvitation->getTimeRefuse()->format('d/m/Y');
                }
            } else {
                $status = 'En attente';
            }
            $organizationInvitation->setStatusTxt($status);
        }

        // fil arianne
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure',$this->generateUrl('app_organization_structure_information'));
        $this->breadcrumb->add('Collaborateurs');
        
        // rendu template
        return $this->render('organization/organization/collaborateurs.html.twig', [
            'formInvitation' => $formInvitation,
            'user' => $user,
            'organization' => $organization,
            'formAccesses' => $formAccesses,
            'userAdminOf' => $organizationService->userAdminOf($user, $organization),
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

        // vérifie l'email
        $organizationInvitation = $organizationInvitationRepository->find($id);
        if ($organizationInvitation->getEmail() !== $user->getEmail()) {
            return $this->redirectToRoute('app_organization_invitations');
        }

        // vérifie l'organization
        if (!$organizationInvitation->getOrganization()) {
            return $this->redirectToRoute('app_organization_invitations');
        }

        // ajoute le user à l'organization
        $organizationAccess = new OrganizationAccess();
        $organizationAccess->setUser($user);
        $organizationAccess->setOrganization($organizationInvitation->getOrganization());
        $managerRegistry->getManager()->persist($organizationAccess);
        
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
            rejoindre votre structure '.$organizationInvitation->getOrganization()->getName().'.
        </p>
        ';
        $notificationService->addNotification($organizationInvitation->getAuthor(), 'Votre invitation a été acceptée', $message);

        // notification aux autres membres de l'organization
        foreach ($organizationInvitation->getOrganization()->getOrganizationAccesses() as $organizationAccess) {
            if (!$organizationAccess->getUser() instanceof User) {
                continue;
            }
            // on ne notifie pas le user et l'auteur de l'invitation
            if (in_array($organizationAccess->getUser()->getId(), [$user->getId(), $organizationInvitation->getAuthor()->getId()])) {
                continue;
            }

            $message = '
            <p>
            '.$user->getFirstname().' '.$user->getLastname().' a accepté l’invitation de '.$organizationInvitation->getAuthor()->getFirstname().' '.$organizationInvitation->getAuthor()->getLastname().'
            et vient de rejoindre votre structure '.$organizationInvitation->getOrganization()->getName().'.
            </p>
            ';
            $notificationService->addNotification($organizationAccess->getUser(), 'Une invitation a été acceptée', $message);
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

    #[Route('/comptes/structure/{id}/porteur/{idBacker?}/', name: 'app_organization_backer_edit', requirements: ['id' => '\d+', 'idBacker' => '\d+'])]
    public function backerEdit(
        int $id,
        int $idBacker = null,
        RequestStack $requestStack,
        BackerRepository $backerRepository,
        ImageService $imageService,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        OrganizationRepository $organizationRepository,
        OrganizationService $organizationService,
        BackerService $backerService,
    )
    {
        // le user
        $user = $userService->getUserLogged();

        // l'organization
        $organization = $organizationRepository->find($id);
        
        if (!$organizationService->canViewEdit($user, $organization)) {
            $this->addFlash(FrontController::FLASH_ERROR, 'Vous n\'avez pas les droits pour éditer cette fiche porteur d\'aide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        // regarde si backer
        $backer = null;
        if ($idBacker) {
            $backer = $backerRepository->find($idBacker);
        }
        if (!$backer instanceof Backer) {
            $backer = new Backer();
            $create = true;
        }

        // regarde si le user peu editer la fiche porteur d'aide
        $userCanEditBacker = $organizationService->canEditBacker($user, $organization);
    
        $isLockedByAnother = false;
        $getLock = null;
        $userAdminOf = false;
        if ($backer instanceOf Backer) {
            // gestion lock
            $isLockedByAnother = $backerService->isLockedByAnother($backer, $user);
            if (!$isLockedByAnother) {
            } else {
                $getLock = $backerService->getLock($backer);
            }

            // utilisateur admin de l'organization
            foreach ($backer->getOrganizations() as $organization) {
                if ($organizationService->userAdminOf($user, $organization)) {
                    $userAdminOf = true;
                }
            }
        }

        // formulaire edition porteur
        $formOptions = [
            'is_readonly' => !$userCanEditBacker
        ];
        $form = $this->createForm(BackerEditType::class, $backer, $formOptions);
        if (!$backer->getId() && $organization) {
            $form->get('name')->setData($organization->getName());
            if ($organization->getPerimeter()) {
                $form->get('perimeter')->setData($organization->getPerimeter());
            }
        }
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid() && $userCanEditBacker) {
                // traitement image
                $logoFile = $form->get('logoFile')->getData();
                if ($logoFile instanceof UploadedFile) {
                    $backer->setLogo(Backer::FOLDER.'/'.$imageService->getSafeFileName($logoFile->getClientOriginalName()));
                    $backer->setLogoFile(null);
                    $imageService->sendUploadedImageToCloud($logoFile, Backer::FOLDER, $backer->getLogo());
                }

                $managerRegistry->getManager()->persist($backer);

                // assigne le porteur d'aide à l'organization
                $organization->setBacker($backer);
                $managerRegistry->getManager()->persist($organization);

                 // sauvegarde
                $managerRegistry->getManager()->flush();

                // on envoi une notification à tous les membres de l'organization pour les prévenir de l'update, sauf l'auteur de la modication
                if (isset($create)) {
                    $title = 'La fiche du porteur d\'aide '.$backer->getName().' à été créee';
                    $message = 
                    '<p>
                    '.$user->getFirstname().' '.$user->getLastname().' de la structure '.$organization->getName(). ' a créé la fiche du porteur d\'aide '.$backer->getName().'.
                    </p>'
                    ;
                } else {
                    $title = 'La fiche du porteur d\'aide '.$backer->getName().' à été modifiée';
                    $message = 
                    '<p>
                    '.$user->getFirstname().' '.$user->getLastname().' de la structure '.$organization->getName(). ' a mis à jour la fiche du porteur d\'aide '.$backer->getName().'.
                    </p>'
                    ;
                }

                $organizationService->sendNotificationToMembers(
                    $organization,
                    $title,
                    $message,
                    [$user]
                );

                // message ok
                if (isset($create)) {
                    $this->addFlash(FrontController::FLASH_SUCCESS, 'La fiche porteur d\'aide a bien été créée. Elle sera validée par un administrateur avant d\'être publiée');
                } else {
                    $this->addFlash(FrontController::FLASH_SUCCESS, 'La fiche porteur d\'aide a bien été modifiée.');
                }
                

                // redirection
                return $this->redirectToRoute('app_organization_backer_edit', ['id' => $organization->getId(), 'idBacker' => $backer->getId()]);
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Le formulaire contient des erreurs.');
            }
        }

        // fil arianne
        $this->breadcrumb->add(
            'Mon compte',
            $this->generateUrl('app_user_dashboard')
        );
        $this->breadcrumb->add(
            'Edition fiche porteur d\'aide'
        );

        return $this->render('user/backer/edit.html.twig', [
            'organization' => $organization,
            'backer' => $backer,
            'form' => $form,
            'user_backer' => true,
            'user_backer_id' => $backer instanceof Backer ? $backer->getId() : null,
            'userCanEditBacker' => $userCanEditBacker,
            'isLockedByAnother' => $isLockedByAnother,
            'getLock' => $getLock,
            'userAdminOf' => $userAdminOf
        ]);
    }

    #[Route('/comptes/structure/porteur/ajax-lock/', name: 'app_organization_backer_ajax_lock', options: ['expose' => true])]
    public function ajaxLock(
        RequestStack $requestStack,
        BackerRepository $backerRepository,
        BackerService $backerService,
        UserService $userService
    ) : JsonResponse
    {
        try {
            // verification requete interne
            $request = $requestStack->getCurrentRequest();
            $origin = $request->headers->get('origin');
            $infosOrigin = parse_url($origin);
            $hostOrigin = $infosOrigin['host'] ?? null;
            $serverName = $request->getHost();
    
            if ($hostOrigin !== $serverName) {
                // La requête n'est pas interne, retourner une erreur
                throw $this->createAccessDeniedException('This action can only be performed by the server itself.');
            }
            
            // recupere id
            $id = (int) $requestStack->getCurrentRequest()->get('id', 0);
            if (!$id) {
                throw new \Exception('Id manquant');
            }

            // le user
            $user = $userService->getUserLogged();
            if (!$user instanceof User) {
                throw new \Exception('User manquant');
            }

            // charge backer
            $backer = $backerRepository->find($id);
            if (!$backer instanceof Backer) {
                throw new \Exception('Fiche manquante');
            }

            // verifie que le user peut lock
            $canLock = $backerService->canUserLock($backer, $user);
            if (!$canLock) {
                throw new \Exception('Vous ne pouvez pas bloquer cette fiche porteur');
            }

            // regarde si deja lock
            $isLockedByAnother = $backerService->isLockedByAnother($backer, $user);
            if ($isLockedByAnother) {
                throw new \Exception('Fiche déjà lock');
            }
            
            // la débloque
            $backerService->lock($backer, $user);

            // retour
            return new JsonResponse([
                'success' => true
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return new JsonResponse([
                'success' => false
            ]);
        }
    }

    #[Route('/comptes/structure/{id}/porteur/{idBacker}/unlock/', name: 'app_organization_backer_unlock', requirements: ['id' => '\d+', 'idBacker' => '\d+'])]
    public function unlock(
        $id,
        $idBacker,
        OrganizationRepository $organizationRepository,
        BackerRepository $backerRepository,
        OrganizationService $organizationService,
        UserService $userService,
        BackerService $backerService
    ): Response
    {
        try {
            // le user
            $user = $userService->getUserLogged();
            if (!$user instanceof User) {
                throw new \Exception('User invalid');
            }

            // l'organization
            $organization = $organizationRepository->find($id);
            if (!$organization instanceof Organization) {
                throw new \Exception('Structure invalide');
            }

            // la fiche porteur
            $backer = $backerRepository->find($idBacker);
            if (!$backer instanceof Backer) {
                throw new \Exception('Fiche invalide');
            }

            // verifie que le user est admin de l'organization qui gère la fiche porteur
            if (!$organizationService->userAdminOf($user, $organization)) {
                throw new \Exception('Utilisateur non autorisé');
            }

            // suppression du lock
            $backerService->unlock($backer);

            // message
            $this->addFlash(FrontController::FLASH_SUCCESS, 'Fiche porteur d\'aides débloquée');

            // retour
            return $this->redirectToRoute('app_user_dashboard');
        } catch (\Exception $e) {
            // message
            $this->addFlash(FrontController::FLASH_ERROR, 'Impossible de débloquer la fiche porteur d\'aides');

            // retour
            return $this->redirectToRoute('app_organization_backer_edit', ['id' => $organization->getId(), 'idBacker' => $backer->getId()]);
        }
    }

    #[Route('/comptes/structure/porteur/ajax-unlock/', name: 'app_organization_backer_ajax_unlock', options: ['expose' => true])]
    public function ajaxUnlock(
        RequestStack $requestStack,
        BackerRepository $backerRepository,
        BackerService $backerService,
        UserService $userService
    ) : JsonResponse
    {
        try {
            // verification requete interne
            $request = $requestStack->getCurrentRequest();
            $origin = $request->headers->get('origin');
            $infosOrigin = parse_url($origin);
            $hostOrigin = $infosOrigin['host'] ?? null;
            $serverName = $request->getHost();
    
            if ($hostOrigin !== $serverName) {
                // La requête n'est pas interne, retourner une erreur
                throw $this->createAccessDeniedException('This action can only be performed by the server itself.');
            }
            
            // recupere id
            $id = (int) $requestStack->getCurrentRequest()->get('id', 0);
            if (!$id) {
                throw new \Exception('Id manquant');
            }

            // user
            $user = $userService->getUserLogged();
            if (!$user instanceof User) {
                throw new \Exception('User manquant');
            }

            // charge backer
            $backer = $backerRepository->find($id);
            if (!$backer instanceof Backer) {
                throw new \Exception('Fiche porteur manquante');
            }

            // verifie que le user peut lock
            $canLock = $backerService->canUserLock($backer, $user);
            if (!$canLock) {
                throw new \Exception('Vous ne pouvez pas bloquer cette fiche porteur d\'aide');
            }

            // la débloque
            $backerService->unlock($backer);

            // retour
            return new JsonResponse([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false
            ]);
        }
    }
}
