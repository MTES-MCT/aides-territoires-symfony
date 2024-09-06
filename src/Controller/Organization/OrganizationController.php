<?php

namespace App\Controller\Organization;

use App\Controller\FrontController;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerAskAssociate;
use App\Entity\Log\LogBackerEdit;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationInvitation;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Form\Backer\BackerAskAssociateType;
use App\Form\Backer\BackerEditType;
use App\Form\Organization\OrganizationDatasType;
use App\Form\Organization\OrganizationEditType;
use App\Form\Organization\OrganizationInvitationSendType;
use App\Form\User\OrganizationChoiceType;
use App\Repository\Backer\BackerAskAssociateRepository;
use App\Repository\Backer\BackerRepository;
use App\Repository\Organization\OrganizationInvitationRepository;
use App\Repository\Organization\OrganizationRepository;
use App\Repository\Perimeter\PerimeterDataRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Security\Voter\InternalRequestVoter;
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
    ): Response {
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
            if (!$organizationService->canEdit($user, $organization)) {
                return $this->redirectToRoute('app_user_dashboard');
            }

            // si le user n'as pas de périmètre on essaye de lui en attribuer un à partir de l'organization
            if ($organization->getPerimeter() && !$user->getPerimeter()) {
                $user->setPerimeter($organization->getPerimeter());
                $managerRegistry->getManager()->persist($user);
                $managerRegistry->getManager()->flush();
            }
        }

        // formulaire edition organization
        $form = $this->createForm(OrganizationEditType::class, $organization);
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // essaye de déterminer le departement
                if ($organization->getPerimeter() && $organization->getPerimeter()->getDepartments()) {
                    $departementsCode = $organization->getPerimeter()->getDepartments();
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
                    $user->addOrganization($organization);
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
        $this->breadcrumb->add('Mon compte', $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add($organization->getId() ? $organization->getName() : 'Nouvelle structure');

        // rendu template
        return $this->render('organization/organization/structure_information.html.twig', [
            'form' => $form,
            'organization' => $organization,
            'organization_edited_id' => $organization->getId(),
        ]);
    }
    #[Route('/comptes/structure/donnees-cles/', name: 'app_organization_donnees_cles')]
    public function donneesCles(
        RequestStack $requestStack
    ): Response {
        // page obsolète
        return $this->redirectToRoute('app_user_dashboard');

        $this->breadcrumb->add('Mon compte', $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure', $this->generateUrl('app_organization_structure_information'));
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
        int $id,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        OrganizationService $organizationService
    ): Response {
        // le user
        $user = $userService->getUserLogged();
        // la structure
        $organization = $managerRegistry->getRepository(Organization::class)->find($id);
        if (!$organization instanceof Organization) {
            return $this->redirectToRoute('app_user_dashboard');
        }
        // on verifie que l'utilisateur peut éditer
        if (!$organizationService->canEdit($user, $organization)) {
            return $this->redirectToRoute('app_organization_donnees_cles');
        }

        // formulaire
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

        // fil d'arianne
        $this->breadcrumb->add('Mon compte', $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure', $this->generateUrl('app_organization_structure_information'));
        $this->breadcrumb->add('Données clés');

        // rendu template
        return $this->render('organization/organization/donnees_cles_details.html.twig', [
            'form' => $formOrganizationDatas,
            'organization' => $organization,
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
    ): Response {
        $user = $userService->getUserLogged();
        $organization = $organizationRepository->find($id);

        // verifie l'organization
        if (!$organization instanceof Organization) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // verifie que l'utilisateur peut éditer
        if (!$organizationService->canEdit($user, $organization)) {
            return $this->redirectToRoute('app_user_dashboard');
        }

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
                    return $this->redirectToRoute('app_organization_collaborateurs', ['id' => $organization->getId()]);
                }

                // verifie que la personne ne fait pas déjà partie de l'organization
                if ($organization->getBeneficiairies()->filter(function ($beneficiary) use ($organizationInvitation) {
                    return $beneficiary->getEmail() === $organizationInvitation->getEmail();
                })->count()) {
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
                    $status = 'Exclu le ' . $userInvitation->getTimeExclude()->format('d/m/Y');
                } elseif ($userInvitation && $userInvitation->getTimeAccept()) {
                    $status = 'Accepté le ' . $userInvitation->getTimeAccept()->format('d/m/Y');
                    if ($userInvitation->getGuest() !== $user) {
                        $excludable = true;
                    }
                } elseif ($userInvitation && $userInvitation->getTimeRefuse()) {
                    $status = 'Refusé le ' . $userInvitation->getTimeRefuse()->format('d/m/Y');
                }
                $collaborators[] = [
                    'name' => $beneficiary->getFirstname() . ' ' . $beneficiary->getLastname(),
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
                    $status = 'Exclu le ' . $organizationInvitation->getTimeExclude()->format('d/m/Y');
                } elseif ($organizationInvitation && $organizationInvitation->getTimeAccept()) {
                    $status = 'Accepté le ' . $organizationInvitation->getTimeAccept()->format('d/m/Y');
                    $excludable = true;
                } elseif ($organizationInvitation && $organizationInvitation->getTimeRefuse()) {
                    $status = 'Refusé le ' . $organizationInvitation->getTimeRefuse()->format('d/m/Y');
                }
            } else {
                $status = 'En attente';
            }
            $collaborators[] = [
                'name' => $organizationInvitation->getFirstname() . ' ' . $organizationInvitation->getLastname(),
                'email' => $organizationInvitation->getEmail(),
                'role' => '',
                'dateInvite' => ($organizationInvitation->getDateCreate()) ? $organizationInvitation->getDateCreate()->format('d/m/Y') : '',
                'status' => $status,
                'excludable' => $excludable,
                'invitationId' => $organizationInvitation->getId(),
            ];
        }

        // fil arianne
        $this->breadcrumb->add('Mon compte', $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure', $this->generateUrl('app_organization_structure_information'));
        $this->breadcrumb->add('Collaborateurs');

        // rendu template
        return $this->render('organization/organization/collaborateurs.html.twig', [
            'formInvitation' => $formInvitation,
            'collaborators' => $collaborators,
            'user' => $user,
            'organization' => $organization,
        ]);
    }

    #[Route('/comptes/structure/invitations/', name: 'app_organization_invitations')]
    public function invitations(
        UserService $userService,
        OrganizationInvitationRepository $organizationInvitationRepository
    ): Response {
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
    ): Response {
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
            ' . $user->getFirstname() . ' ' . $user->getLastname() . ' a accepté votre invitation et vient de
            rejoindre votre structure ' . $organization->getName() . '.
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
            ' . $user->getFirstname() . ' ' . $user->getLastname() . ' a accepté l’invitation de ' . $organizationInvitation->getAuthor()->getFirstname() . ' ' . $organizationInvitation->getAuthor()->getLastname() . '
            et vient de rejoindre votre structure ' . $organization->getName() . '.
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
    ): Response {
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
            ' . $user->getFirstname() . ' ' . $user->getLastname() . ' a refusé votre invitation.
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
    ): Response {
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
            ' . $user->getFirstname() . ' ' . $user->getLastname() . ' vous à exclu de l\'organization ' . $organizationName . '.
        </p>
        ';

        if ($organizationInvitation->getGuest()) {
            $notificationService->addNotification($organizationInvitation->getGuest(), 'Vous avez été exclu de ' . $organizationName, $message);
        }

        // message
        $this->addFlash(
            FrontController::FLASH_SUCCESS,
            'Vous avez exclu ' . $organizationInvitation->getFirstname() . ' ' . $organizationInvitation->getLastname() . ' de l\'organization.'
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

        return $this->redirectToRoute('app_organization_collaborateurs', ['id' => $organizationInvitation->getOrganization()->getId()]);
    }

    #[Route('/comptes/structure/{id}/porteur/{idBacker?}/', name: 'app_organization_backer_edit', requirements: ['id' => '\d+', 'idBacker' => '\d+'])]
    public function backerEdit(
        int $id,
        int $idBacker = null,
        RequestStack $requestStack,
        BackerRepository $backerRepository,
        BackerAskAssociateRepository $backerAskAssociateRepository,
        ImageService $imageService,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        OrganizationRepository $organizationRepository,
        OrganizationService $organizationService,
        NotificationService $notificationService,
        BackerService $backerService
    ) {
        // le user
        $user = $userService->getUserLogged();

        // l'organization
        $organization = $organizationRepository->find($id);

        if (!$organizationService->canEdit($user, $organization)) {
            $this->addFlash(FrontController::FLASH_ERROR, 'Vous n\'avez pas les droits pour éditer cette fiche porteur d\'aide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        // si l'organization à un porteur associé mais qu'on a pas l'id en paramètre, on redirige
        if ($organization->getBacker() && !$idBacker) {
            return $this->redirectToRoute('app_organization_backer_edit', ['id' => $organization->getId(), 'idBacker' => $organization->getBacker()->getId()]);
        }

        // regarde si backer
        $backer = null;
        if ($idBacker) {
            $backer = $backerRepository->find($idBacker);
            // on vérifie que le porteur d'aide peu bien être éditer par l'utilisateur de cette organization
            if (!$backerService->userCanEdit($user, $backer)) {
                $this->addFlash(FrontController::FLASH_ERROR, 'Vous n\'avez pas les droits pour éditer cette fiche porteur d\'aide.');
                return $this->redirectToRoute('app_user_dashboard');
            }
        }
        if (!$backer instanceof Backer) {
            $backer = new Backer();
            $create = true;
        }

        // demandes d'associations à un porteur refusées
        $backerAskAssociatesRefused = $backerAskAssociateRepository->findOrganizationRefused($organization);

        // demande d'association à un porteur en attente
        $backerAskAssociatePending = $backerAskAssociateRepository->findOrganizationPending($organization);

        if (
            !$backerAskAssociatePending // si pas de demande d'association en attente
        ) {
            $backerAskAssociate = new BackerAskAssociate();
            $backerAskAssociate->setUser($user);
            $backerAskAssociate->setOrganization($organization);
            $formAskAssociate = $this->createForm(BackerAskAssociateType::class, $backerAskAssociate);
            $formAskAssociate->handleRequest($requestStack->getCurrentRequest());
            if ($formAskAssociate->isSubmitted()) {
                if ($formAskAssociate->isValid()) {
                    $managerRegistry->getManager()->persist($backerAskAssociate);
                    $managerRegistry->getManager()->flush();
                    $this->addFlash(FrontController::FLASH_SUCCESS, 'Votre demande d\'association a bien été envoyée.');
                    return $this->redirectToRoute('app_organization_backer_edit', ['id' => $organization->getId(), 'idBacker' => 0]);
                } else {
                    $this->addFlash(FrontController::FLASH_ERROR, 'Le formulaire contient des erreurs.');
                }
            }
        }


        // formulaire edition porteur
        $form = $this->createForm(BackerEditType::class, $backer);
        if (!$backer->getId() && $organization) {
            $form->get('name')->setData($organization->getName());
            if ($organization->getPerimeter()) {
                $form->get('perimeter')->setData($organization->getPerimeter());
            }
        }
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // traitement image
                $logoFile = $form->get('logoFile')->getData();
                if ($logoFile instanceof UploadedFile) {
                    $backer->setLogo(Backer::FOLDER . '/' . $imageService->getSafeFileName($logoFile->getClientOriginalName()));
                    $backer->setLogoFile(null);
                    $imageService->sendUploadedImageToCloud($logoFile, Backer::FOLDER, $backer->getLogo());
                }

                $managerRegistry->getManager()->persist($backer);

                // assigne le porteur d'aide à l'organization
                $organization->setBacker($backer);
                $managerRegistry->getManager()->persist($organization);

                // sauvegarde
                $managerRegistry->getManager()->flush();

                // on envoi une notification à tous les membres de l'organization pour les prévenir de l'update
                foreach ($organization->getBeneficiairies() as $beneficiary) {
                    if ($beneficiary->getId() != $user->getId()) {
                        if (isset($create)) {
                            $notificationService->addNotification(
                                $beneficiary,
                                'La fiche du porteur d\'aide ' . $backer->getName() . ' à été créee',
                                '<p>
                                ' . $user->getFirstname() . ' ' . $user->getLastname() . ' de la structure ' . $organization->getName() . ' a créé la fiche du porteur d\'aide ' . $backer->getName() . '.
                                </p>'
                            );
                        } else {
                            $notificationService->addNotification(
                                $beneficiary,
                                'La fiche du porteur d\'aide ' . $backer->getName() . ' à été modifiée',
                                '<p>
                                ' . $user->getFirstname() . ' ' . $user->getLastname() . ' de la structure ' . $organization->getName() . ' a mis à jour la fiche du porteur d\'aide ' . $backer->getName() . '.
                                </p>'
                            );
                        }
                    }
                }

                // log
                $logBackerEdit = new LogBackerEdit();
                $logBackerEdit->setBacker($backer);
                $logBackerEdit->setUser($user);
                $logBackerEdit->setOrganization($organization);
                $managerRegistry->getManager()->persist($logBackerEdit);
                $managerRegistry->getManager()->flush();

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
            'formAskAssociate' => $formAskAssociate ?? null,
            'backerAskAssociatePending' => $backerAskAssociatePending,
            'backerAskAssociatesRefused' => $backerAskAssociatesRefused
        ]);
    }

    #[Route('/comptes/structure/porteur/ajax-lock/', name: 'app_organization_backer_ajax_lock', options: ['expose' => true], methods: ['POST'])]
    public function ajaxLock(
        RequestStack $requestStack,
        BackerRepository $backerRepository,
        BackerService $backerService,
        UserService $userService
    ): JsonResponse {
        try {
            // verification requête interne
            if (!$this->isGranted(InternalRequestVoter::IDENTIFIER)) {
                throw $this->createAccessDeniedException(InternalRequestVoter::MESSAGE_ERROR);
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
                throw new \Exception('Fiche déjà bloquée');
            }

            // la débloque
            $backerService->lock($backer, $user);

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

    #[Route('/comptes/structure/{id}/porteur/{idBacker}/unlock/', name: 'app_organization_backer_unlock', requirements: ['id' => '\d+', 'idBacker' => '\d+'])]
    public function unlock(
        $id,
        $idBacker,
        OrganizationRepository $organizationRepository,
        BackerRepository $backerRepository,
        OrganizationService $organizationService,
        UserService $userService,
        BackerService $backerService
    ): Response {
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

            // verifie que le user peut lock
            $canLock = $backerService->canUserLock($backer, $user);
            if (!$canLock) {
                throw new \Exception('Vous ne pouvez pas bloquer cette fiche porteur');
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
    ): JsonResponse {
        try {
            // verification requête interne
            if (!$this->isGranted(InternalRequestVoter::IDENTIFIER)) {
                throw $this->createAccessDeniedException(InternalRequestVoter::MESSAGE_ERROR);
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
