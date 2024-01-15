<?php

namespace App\Controller\Organization;

use App\Controller\FrontController;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationInvitation;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\Notification;
use App\Form\Organization\OrganizationInvitationSendType;
use App\Form\User\RegisterType;
use App\Repository\Organization\OrganizationInvitationRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Service\Email\EmailService;
use App\Service\Notification\NotificationService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
        PerimeterRepository $perimeterRepository
    ): Response
    {
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure');
        
        $user = $userService->getUserLogged();
        $organization = $user->getDefaultOrganization();
        $form = $this->createForm(RegisterType::class, $user, ['onlyOrganization' => true]);
        if ($organization) {
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
    public function donneesCles(UserService $userService, ManagerRegistry $managerRegistry, RequestStack $requestStack): Response
    {
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Ma structure',$this->generateUrl('app_organization_structure_information'));
        $this->breadcrumb->add('Données clés');
        
        $user = $userService->getUserLogged();
        $organization = $user->getOrganizations()[0];
            
        $form = $this->createFormBuilder($organization)
            ->add('inhabitantsNumber',NumberType::class,['label_html' => true,'label'=>'Habitants&nbsp;:','required'=>false,'help' => ''])
            ->add('votersNumber',NumberType::class,['label_html' => true,'label'=>'Votants&nbsp;:','required'=>false,'help' => ''])
            
            ->add('corporatesNumber',NumberType::class,['label_html' => true,'label'=>'Entreprise&nbsp;:','required'=>false,'help' => ''])
            ->add('shopsNumber',NumberType::class,['label_html' => true,'label'=>'Commerce&nbsp;:','required'=>false,'help' => ''])
            ->add('associationsNumber',NumberType::class,['label_html' => true,'label'=>'Association&nbsp;:','required'=>false,'help' => ''])

            ->add('municipalRoads',NumberType::class,['label_html' => true,'label'=>'Routes communales (kms)&nbsp;:','required'=>false,'help' => ''])
            ->add('departmentalRoads',NumberType::class,['label_html' => true,'label'=>'Routes départementales (kms)&nbsp;:','required'=>false,'help' => ''])
            ->add('tramRoads',NumberType::class,['label_html' => true,'label'=>'Tramway (kms)&nbsp;:','required'=>false,'help' => ''])
            ->add('lamppostNumber',NumberType::class,['label_html' => true,'label'=>'Lampadaires&nbsp;:','required'=>false,'help' => ''])
            ->add('bridgeNumber',NumberType::class,['label_html' => true,'label'=>'Ponts&nbsp;:','required'=>false,'help' => ''])

            ->add('libraryNumber',NumberType::class,['label_html' => true,'label'=>'Bibliothèque&nbsp;:','required'=>false,'help' => ''])
            
            ->add('medialibraryNumber',NumberType::class,['label_html' => true,'label'=>'Médiathèque&nbsp;:','required'=>false,'help' => ''])
            ->add('theaterNumber',NumberType::class,['label_html' => true,'label'=>'Théâtre&nbsp;:','required'=>false,'help' => ''])
            ->add('cinemaNumber',NumberType::class,['label_html' => true,'label'=>'Cinéma&nbsp;:','required'=>false,'help' => ''])
            ->add('museumNumber',NumberType::class,['label_html' => true,'label'=>'Musée&nbsp;:','required'=>false,'help' => ''])

            ->add('nurseryNumber',NumberType::class,['label_html' => true,'label'=>'Crèche&nbsp;:','required'=>false,'help' => ''])
            ->add('kindergartenNumber',NumberType::class,['label_html' => true,'label'=>'École maternelle&nbsp;:','required'=>false,'help' => ''])
            ->add('primarySchoolNumber',NumberType::class,['label_html' => true,'label'=>'École élémentaire&nbsp;:','required'=>false,'help' => ''])
            ->add('recCenterNumber',NumberType::class,['label_html' => true,'label'=>'Centre de loisirs&nbsp;:','required'=>false,'help' => ''])
            ->add('middleSchoolNumber',NumberType::class,['label_html' => true,'label'=>'Collège&nbsp;:','required'=>false,'help' => ''])
            ->add('highSchoolNumber',NumberType::class,['label_html' => true,'label'=>'Lycée&nbsp;:','required'=>false,'help' => ''])
            ->add('universityNumber',NumberType::class,['label_html' => true,'label'=>'Université&nbsp;:','required'=>false,'help' => ''])
            
            ->add('tennisCourtNumber',NumberType::class,['label_html' => true,'label'=>'Court de tennis&nbsp;:','required'=>false,'help' => ''])
            ->add('footballFieldNumber',NumberType::class,['label_html' => true,'label'=>'Terrain de football&nbsp;:','required'=>false,'help' => ''])
            ->add('runningTrackNumber',NumberType::class,['label_html' => true,'label'=>'Piste d\'athlétismes&nbsp;:','required'=>false,'help' => ''])
            ->add('otherOutsideStructureNumber',NumberType::class,['label_html' => true,'label'=>'Structure extérieure autre&nbsp;:','required'=>false,'help' => ''])
            ->add('coveredSportingComplexNumber',NumberType::class,['label_html' => true,'label'=>'Complexe sportif couvert&nbsp;:','required'=>false,'help' => ''])
            ->add('swimmingPoolNumber',NumberType::class,['label_html' => true,'label'=>'Piscine&nbsp;:','required'=>false,'help' => ''])
            
            ->add('placeOfWorshipNumber',NumberType::class,['label_html' => true,'label'=>'Lieux de cultes&nbsp;:','required'=>false,'help' => ''])
            ->add('cemeteryNumber',NumberType::class,['label_html' => true,'label'=>'Cimetières&nbsp;:','required'=>false,'help' => ''])
            
            ->add('protectedMonumentNumber',NumberType::class,['label_html' => true,'label'=>'Monument classé&nbsp;:','required'=>false,'help' => ''])
            ->add('forestNumber',NumberType::class,['label_html' => true,'label'=>'Forêt (en hactares)&nbsp;:','required'=>false,'help' => ''])
            
                
            ->add('save',SubmitType::class,['label_html' => true,'label'=>'Mettre à jour'])
            ->getForm();

        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($organization); 
            $entityManager->flush();
            $this->addFlash(FrontController::FLASH_SUCCESS, 'Vos modifications ont été enregistrées avec succès.');
            return $this->redirectToRoute('app_organization_donnees_cles');
        }

        return $this->render('organization/organization/donnees_cles.html.twig', [
            'form' => $form->createView(),
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
        $users = $organization ? $organization->getBeneficiairies() : [];

        $organizationInvitation = new OrganizationInvitation();
        $formInvitation = $this->createForm(OrganizationInvitationSendType::class, $organizationInvitation);
        $formInvitation->handleRequest($requestStack->getCurrentRequest());
        if ($formInvitation->isSubmitted()) {
            if ($formInvitation->isValid()) {
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
                // sauvegarde
                $organizationInvitation->setAuthor($user);
                $organizationInvitation->setOrganization($user->getDefaultOrganization());
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

        // la liste des invitations
        $organizationInvitations = $organizationInvitationRepository->findBy([
            'organization' => $organization
        ]);

        return $this->render('organization/organization/collaborateurs.html.twig', [
            'formInvitation' => $formInvitation,
            'collaborators' => $users,
            'user' => $user,
            'organizationInvitations' => $organizationInvitations
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

}
