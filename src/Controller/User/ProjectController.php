<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Aid\AidProject;
use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Form\Project\ProjectEditType;
use App\Form\User\Project\AidProjectDeleteType;
use App\Form\User\Project\AidProjectStatusType;
use App\Form\User\Project\ProjectDeleteType;
use App\Repository\Aid\AidProjectRepository;
use App\Repository\Aid\AidRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\Project\ProjectValidatedRepository;
use App\Service\Image\ImageService;
use App\Service\Notification\NotificationService;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProjectController extends FrontController
{
    const NB_PROJECT_BY_PAGE = 30;


    #[Route('/comptes/projets/', name: 'app_user_project_structure')]
    public function index(
        UserService $userService,
        ProjectRepository $projectRepository,
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        NotificationService $notificationService
    ): Response
    {
        $user = $userService->getUserLogged();

        // formulaire suppression projet
        $formDeleteProject = $this->createForm(ProjectDeleteType::class, null, [
            'action' => $this->generateUrl('app_user_project_structure')
        ]);
        $formDeleteProject->handleRequest($requestStack->getCurrentRequest());
        if ($formDeleteProject->isSubmitted()) {
            if ($formDeleteProject->isValid()) {


                // notification aux autres membres de l'organization
                $project = $projectRepository->find($formDeleteProject->get('idProject')->getData());
                if ($project instanceof Project && $project->getOrganization()) {
                    foreach ($project->getOrganization()->getBeneficiairies() as $beneficiary) {
                        if ($beneficiary->getId() != $user->getId()) {
                            $notificationService->addNotification(
                                $beneficiary,
                                'Un projet a été supprimé',
                                '<p>
                                '.$user->getFirstname().' '.$user->getLastname().' a supprimé le projet '.$project->getName().'.
                                </p>'
                            );
                        }
                    }
                }

                // suppression
                $managerRegistry->getManager()->remove($projectRepository->find($formDeleteProject->get('idProject')->getData()));
                $managerRegistry->getManager()->flush();

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Le projet a bien été supprimé.'
                );

                // redirection
                return $this->redirectToRoute('app_user_project_structure');
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Vous ne pouvez pas supprimer ce projet.'
                );
            }
        }

        // projets
        $projects = $projectRepository->findBy(
            [
                'author' => $user
                // 'organization' => $user->getDefaultOrganization()
            ],
            [
                'timeCreate' => 'DESC'
            ]
        );

        // fil arianne
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets de ma structure");
        
        // rendu template
        return $this->render('user/project/index.html.twig', [
            'projects' => $projects,
            'formDeleteProject' => $formDeleteProject
        ]);
    }

    #[Route('/comptes/projets-favoris/', name: 'app_user_project_favoris')]
    public function projetFavoris(UserService $userService): Response
    {
        $user = $userService->getUserLogged();
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets favoris");
        return $this->render('user/project/favoris.html.twig', [
            'user' => $user
        ]);
    }

    
    #[Route('/comptes/projets/details/{id}-{slug}/', name: 'app_user_project_details_fiche_projet', requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+'])]
    public function details(
        $id,
        $slug,
        ProjectRepository $ProjectRepository,
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        ImageService $imageService,
        NotificationService $notificationService
    ): Response
    {
        $project = $ProjectRepository->findOneBy(
            [
                'id' => $id,
                'slug' => $slug
            ]
        );
        $user = $userService->getUserLogged();
        
        if (!$project instanceof Project || !$userService->isMemberOfOrganization($project->getOrganization(), $user)) {
            return $this->redirectToRoute('app_user_project_structure');
        }
        
        $form = $this->createForm(ProjectEditType::class, $project);

        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // traitement image
                $imageFile = $form->get('imageUploadedFile')->getData();
                if ($imageFile instanceof UploadedFile) {
                    $project->setImage(Project::FOLDER.'/'.$imageService->getSafeFileName($imageFile->getClientOriginalName()));
                    $imageService->sendUploadedImageToCloud($imageFile, Project::FOLDER, $project->getImage());
                }

                // si demande de projet public
                if ($project->getStatus() == Project::STATUS_DRAFT && $project->isIsPublic()) {
                    $project->setStatus(Project::STATUS_REVIEWABLE);
                }

                // sauvegarde
                $managerRegistry->getManager()->persist($project); 
                $managerRegistry->getManager()->flush();

                // notification aux autres membres de l'organization
                foreach ($project->getOrganization()->getBeneficiairies() as $beneficiary) {
                    if ($beneficiary->getId() != $user->getId()) {
                        $notificationService->addNotification(
                            $beneficiary,
                            'Un projet a été mis à jour',
                            '<p>
                            '.$user->getFirstname().' '.$user->getLastname().' a modifié les informations du projet
                            <a href="'.$this->generateUrl('app_user_project_details_fiche_projet', ['id' => $project->getId(), 'slug' => $project->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL).'">'.$project->getName().'</a>.
                            </p>'
                        );
                    }
                }

                // message
                $this->tAddFlash(
                    FrontController::FLASH_SUCCESS,
                    'Vos modifications ont été enregistrées avec succès.'
                );

                // redirection
                return $this->redirectToRoute('app_user_project_details_fiche_projet', [
                    'id' => $id,
                    'slug' => $slug
                ]);
            } else {
                $formErrors = true;
            }
        }

        // fil arianne
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Projet ".$project->getName(),null);

        // rendu template
        return $this->render('user/project/fiche_projet.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'formErrors' => $formErrors ?? false
        ]);
    }

    #[Route('/comptes/projets/creation/', name: 'app_user_project_creation_projet')]
    public function creation(
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        ImageService $imageService,
        NotificationService $notificationService
    ): Response
    {
        $project = new Project();
        $user = $userService->getUserLogged();
        
        // formulaire
        $form = $this->createForm(ProjectEditType::class, $project);
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // traitement image
                $imageFile = $form->get('imageUploadedFile')->getData();
                if ($imageFile instanceof UploadedFile) {
                    $project->setImage(Project::FOLDER.'/'.$imageService->getSafeFileName($imageFile->getClientOriginalName()));
                    $imageService->sendUploadedImageToCloud($imageFile, Project::FOLDER, $project->getImage());
                }

                // données additionnelles
                $project->setStatus(Project::STATUS_DRAFT);
                $project->setOrganization($user->getDefaultOrganization());
                $project->setAuthor($user);
                
                // si demande de projet public
                if ($project->getStatus() == Project::STATUS_DRAFT && $project->isIsPublic()) {
                    $project->setStatus(Project::STATUS_REVIEWABLE);
                }

                // sauvegarde
                $managerRegistry->getManager()->persist($project); 
                $managerRegistry->getManager()->flush();

                // notification aux autres membres de l'organization
                foreach ($project->getOrganization()->getBeneficiairies() as $beneficiary) {
                    if ($beneficiary->getId() != $user->getId()) {
                        $notificationService->addNotification(
                            $beneficiary,
                            'Un projet a été créé',
                            '<p>
                            '.$user->getFirstname().' '.$user->getLastname().' a créé le projet
                            <a href="'.$this->generateUrl('app_user_project_details_fiche_projet', ['id' => $project->getId(), 'slug' => $project->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL).'">'.$project->getName().'</a>.
                            </p>'
                        );
                    }
                }

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Votre projet a bien été créé, vous pouvez maintenant chercher des aides.'
                );

                // redirection
                return $this->redirectToRoute('app_user_project_aides', [
                    'id' => $project->getId(),
                    'slug' => $project->getSlug(),
                    'projectCreated' => true
                ]);
            } else {
                $formErrors = true;
            }
        }

        // fil arianne
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Nouveau projet",null);

        // rendu termplate
        return $this->render('user/project/creation_projet.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'formErrors' => $formErrors ?? false
        ]);
    }


    #[Route('/comptes/projets/aides/{id}-{slug}/', name: 'app_user_project_aides', requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+'])]
    public function aides(
        $id,
        ProjectRepository $ProjectRepository,
        AidRepository $aidRepository,
        UserService $userService,
        RequestStack $requestStack,
        AidProjectRepository $aidProjectRepository,
        ManagerRegistry $managerRegistry,
        NotificationService $notificationService
    ): Response
    {
        $projectCreated = $requestStack->getCurrentRequest()->get('projectCreated', 0);

        $project = $ProjectRepository->findOneBy(
            [
                'id' => $id
            ]
        );
        $user = $userService->getUserLogged();
        
        if (!$project instanceof Project || $project->getOrganization()->getId()!=$user->getDefaultOrganization()->getId()) {
            return $this->redirectToRoute('app_user_project_structure');
        }

        // si le projet n'as pas encore d'aide on va essayer d'en trouver pour les suggérer
        $aidsSuggested = [];
        $searchParams = [
            'searchPerimeter' => '',
            'organizationType' => '',
            'keyword' => ''
        ];
        if (count($project->getAidProjects()) == 0) {
            $aidParams = [
                'showInSearch' => true,
            ];
            if ($project->getOrganization() && $project->getOrganization()->getPerimeter()) {
                $aidParams['perimeterFrom'] = $project->getOrganization()->getPerimeter();
                $searchParams['searchPerimeter'] = $aidParams['perimeterFrom']->getId();
                if ($project->getOrganization()->getOrganizationType()) {
                    $aidParams['organizationType'] = $project->getOrganization()->getOrganizationType();
                    $searchParams['organizationType'] = $aidParams['organizationType']->getSlug();
                }
                $aidParams['keyword'] = $project->getName();
                $searchParams['keyword'] = $aidParams['keyword'];
            }

            $aidsSuggested = $aidRepository->findCustom($aidParams);
        }

        // formulaire suppression aidProject
        $formAidProjectDelete = $this->createForm(AidProjectDeleteType::class);
        $formAidProjectDelete->handleRequest($requestStack->getCurrentRequest());
        if ($formAidProjectDelete->isSubmitted()) {
            if ($formAidProjectDelete->isValid()) {
                // suppression
                $aidProject = $aidProjectRepository->find($formAidProjectDelete->get('idAidProject')->getData());
                if ($aidProject instanceof AidProject && $aidProject->getProject()->getId() == $project->getId()) {
                    $managerRegistry->getManager()->remove($aidProject);
                    $managerRegistry->getManager()->flush();
                }

                foreach ($project->getOrganization()->getBeneficiairies() as $beneficiary) {
                    if ($beneficiary->getId() != $user->getId()) {
                        $notificationService->addNotification(
                            $beneficiary,
                            'Une aide a été supprimée d’un projet',
                            '<p>
                            '.$user->getFirstname().' '.$user->getLastname().' a supprimé une aide du projet
                            <a href="'.$this->generateUrl('app_user_project_details_fiche_projet', ['id' => $project->getId(), 'slug' => $project->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL).'">'.$project->getName().'</a>.
                            </p>'
                        );
                    }
                }

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'L\'aide a bien été supprimée.'
                );

                // redirection
                return $this->redirectToRoute('app_user_project_aides', [
                    'id' => $project->getId(),
                    'slug' => $project->getSlug(),
                ]);
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Vous ne pouvez pas supprimer cette aide.'
                );
            }
        }

        // formulaire modification aidProject
        $formAidProjectEditHasError = 0;
        $formAidProjectEdits = [];
        foreach ($project->getAidProjects() as $aidProject) {
            $formAidProjectEdits[$aidProject->getId()] = $this->createForm(AidProjectStatusType::class, $aidProject);
            $formAidProjectEdits[$aidProject->getId()]->handleRequest($requestStack->getCurrentRequest());
            if ($formAidProjectEdits[$aidProject->getId()]->isSubmitted()) {
                if ($formAidProjectEdits[$aidProject->getId()]->isValid()) {
                    // modification
                    $managerRegistry->getManager()->persist($aidProject);
                    $managerRegistry->getManager()->flush();

                    // message
                    $this->addFlash(
                        FrontController::FLASH_SUCCESS,
                        'Le statut de l\'aide a bien été modifié.'
                    );
    
                    // redirection
                    return $this->redirectToRoute('app_user_project_aides', [
                        'id' => $project->getId(),
                        'slug' => $project->getSlug(),
                    ]);
                } else {
                    $formAidProjectEditHasError = $aidProject->getId();
                    $this->addFlash(
                        FrontController::FLASH_ERROR,
                        'Vous ne pouvez pas modifier ce statut d\'aide.'
                    );
                }
            }
        }


        // fil arianne
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Projet ".$project->getName(),null);

        // rendu template
        return $this->render('user/project/aides.html.twig', [
            'project' => $project,
            'aidsSuggested' => $aidsSuggested,
            'searchParams' => $searchParams,
            'formAidProjectDelete' => $formAidProjectDelete,
            'projectCreated' => $projectCreated,
            'formAidProjectEdits' => $formAidProjectEdits,
            'formAidProjectEditHasError' => $formAidProjectEditHasError
        ]);
    }


    #[Route('/comptes/projets/similaires/{id}-{slug}/', name: 'app_user_project_similaires', requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+'])]
    public function similaires(
        $id,
        $slug,
        ProjectRepository $ProjectRepository,
        UserService $userService,
        ProjectValidatedRepository $projectValidatedRepository,
        ProjectRepository $projectRepository,
        RequestStack $requestStack,
        ReferenceService $referenceService
    ): Response
    {
        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);
        
        $project = $ProjectRepository->findOneBy(
            [
                'id' => $id,
                'slug' => $slug
            ]
        );
        $user = $userService->getUserLogged();
        
        if (!$project instanceof Project || $project->getOrganization()->getId()!=$user->getDefaultOrganization()->getId()) {
            return $this->redirectToRoute('app_user_project_structure');
        }

        // Projets subventionnés
        $synonyms = ($project->getProjectReference())
            ? $referenceService->getSynonymes($project->getProjectReference()->getName())
            : null
        ;

        $project_perimeter = ($user->getDefaultOrganization() && $user->getDefaultOrganization()->getPerimeter())
            ? $user->getDefaultOrganization()->getPerimeter()
            : null
        ;
        $projects = [];
        if ($project_perimeter instanceof Perimeter) {
            $projectParams = [
                'perimeter' => $project_perimeter,
                'radius' => 30
            ];
            if ($synonyms) {
                $projectParams = array_merge($projectParams, $synonyms);
            }
            $projects = $projectValidatedRepository->findProjectInRadius($projectParams);
        }

        // pagination project validés
        $adapter = new ArrayAdapter($projects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_PROJECT_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        
        // Projets publics : 
        $projets_publics = [];
        if ($synonyms) {
            $projectParams = $synonyms;
            $projectParams['exclude'] = $project;
            $projectsParams['limit'] = 12;
            $projectsParams['orderBy'] = [
                'sort' => 'p.timeCreate',
                'order' => 'DESC'
            ];
            if ($project_perimeter instanceof Perimeter) {
                $projectParams['perimeterRadius'] = $project_perimeter;
                $projectParams['radius'] = 30;
            }

            $projets_publics = $projectRepository->findPublicProjects($projectParams);

            // Si rien à 30 km, on élargit à 300 km
            if (count($projets_publics) == 0 && $project_perimeter instanceof Perimeter) {
                $projectParams['radius'] = 300;
                $projets_publics = $projectRepository->findPublicProjects($projectParams);
            }
        }   
        
        // fil d'arianne
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Projet ".$project->getName(),null);

        // rendu template
        return $this->render('user/project/similaires.html.twig', [
            'project' => $project,
            'projets_publics' => $projets_publics,
            'myPager' => $pagerfanta,
        ]);
    }
}
