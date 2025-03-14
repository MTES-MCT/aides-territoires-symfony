<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Aid\AidProject;
use App\Entity\Aid\AidSuggestedAidProject;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\Reference\ProjectReference;
use App\Entity\User\User;
use App\Form\Project\ProjectEditType;
use App\Form\User\Project\AidProjectDeleteType;
use App\Form\User\Project\AidProjectStatusType;
use App\Form\User\Project\ProjectDeleteType;
use App\Form\User\Project\ProjectExportType;
use App\Message\User\MsgProjectExportAids;
use App\Repository\Aid\AidProjectRepository;
use App\Repository\Aid\AidSuggestedAidProjectRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\Project\ProjectValidatedRepository;
use App\Security\Voter\InternalRequestVoter;
use App\Service\Aid\AidService;
use App\Service\Export\SpreadsheetExporterService;
use App\Service\File\FileService;
use App\Service\Image\ImageService;
use App\Service\Notification\NotificationService;
use App\Service\Project\ProjectService;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProjectController extends FrontController
{
    public const NB_PROJECT_BY_PAGE = 30;

    #[Route('/projets/vos-projets/', name: 'old_app_user_project_structure')]
    public function oldIndex(): RedirectResponse
    {
        return $this->redirectToRoute('app_user_project_structure');
    }

    #[Route('/comptes/projets/', name: 'app_user_project_structure')]
    public function index(
        UserService $userService,
        ProjectRepository $projectRepository,
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        NotificationService $notificationService
    ): Response {
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
                                '<p>'
                                . $user->getFirstname()
                                . ' '
                                . $user->getLastname()
                                . ' a supprimé le projet '
                                . $project->getName()
                                . '.</p>'
                            );
                        }
                    }
                }

                // suppression
                $managerRegistry->getManager()->remove(
                    $projectRepository->find($formDeleteProject->get('idProject')->getData())
                );
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
        $projects = $projectRepository->findCustom(
            [
                'organizations' => $user->getOrganizations(),
                'orderBy' => [
                    'sort' => 'p.timeCreate',
                    'order' => 'DESC'
                ]
            ]
        );

        // fil arianne
        $this->breadcrumb->add("Mon compte", $this->generateUrl('app_user_dashboard'));
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
        $this->breadcrumb->add("Mon compte", $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets favoris");
        return $this->render('user/project/favoris.html.twig', [
            'user' => $user
        ]);
    }


    #[Route(
        '/comptes/projets/details/{id}-{slug}/',
        name: 'app_user_project_details_fiche_projet',
        requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+']
    )]
    public function details(
        int $id,
        string $slug,
        ProjectRepository $ProjectRepository,
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        ImageService $imageService,
        NotificationService $notificationService
    ): Response {
        $project = $ProjectRepository->findOneBy(
            [
                'id' => $id,
                'slug' => $slug
            ]
        );
        $user = $userService->getUserLogged();

        if (
            !$project instanceof Project
            || !$userService->isMemberOfOrganization($project->getOrganization(), $user)
        ) {
            return $this->redirectToRoute('app_user_project_structure');
        }

        $form = $this->createForm(ProjectEditType::class, $project);

        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // traitement image
                $imageFile = $form->get('imageUploadedFile')->getData();
                if ($imageFile instanceof UploadedFile) {
                    $project->setImage(
                        Project::FOLDER . '/' . $imageService->getSafeFileName($imageFile->getClientOriginalName())
                    );
                    $imageService->sendUploadedImageToCloud($imageFile, Project::FOLDER, $project->getImage());
                }

                // si demande de projet public
                if ($project->getStatus() == Project::STATUS_DRAFT && $project->isIsPublic()) {
                    $project->setStatus(Project::STATUS_REVIEWABLE);
                }

                // si pas le projet n'a pas d'organization on met l'organization par défaut du user
                if (!$project->getOrganization()) {
                    $project->setOrganization($user->getDefaultOrganization());
                }

                // sauvegarde
                $managerRegistry->getManager()->persist($project);
                $managerRegistry->getManager()->flush();

                // notification aux autres membres de l'organization
                if ($project->getOrganization()) {
                    foreach ($project->getOrganization()->getBeneficiairies() as $beneficiary) {
                        if ($beneficiary->getId() != $user->getId()) {
                            $notificationService->addNotification(
                                $beneficiary,
                                'Un projet a été mis à jour',
                                '<p>
                                ' . $user->getFirstname()
                                . ' '
                                . $user->getLastname()
                                . ' a modifié les informations du projet
                                <a href="'
                                . $this->generateUrl(
                                    'app_user_project_details_fiche_projet',
                                    [
                                        'id' => $project->getId(),
                                        'slug' => $project->getSlug()
                                    ],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                )
                                . '">' . $project->getName() . '</a>.
                                </p>'
                            );
                        }
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
        $this->breadcrumb->add("Mon compte", $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Projet " . $project->getName(), null);

        // rendu template
        return $this->render('user/project/fiche_projet.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'formErrors' => $formErrors ?? false,
        ]);
    }

    #[Route('/comptes/projets/creation/', name: 'app_user_project_creation_projet')]
    public function creation(
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        ImageService $imageService,
        NotificationService $notificationService
    ): Response {
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
                    $project->setImage(
                        Project::FOLDER
                        . '/'
                        . $imageService->getSafeFileName($imageFile->getClientOriginalName())
                    );
                    $imageService->sendUploadedImageToCloud($imageFile, Project::FOLDER, $project->getImage());
                }

                // données additionnelles
                $project->setStatus(Project::STATUS_DRAFT);
                if (!$project->getOrganization()) {
                    $project->setOrganization($user->getDefaultOrganization());
                }
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
                            ' . $user->getFirstname() . ' ' . $user->getLastname() . ' a créé le projet
                            <a href="'
                            . $this->generateUrl(
                                'app_user_project_details_fiche_projet',
                                [
                                    'id' => $project->getId(),
                                    'slug' => $project->getSlug()
                                ],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            )
                            . '">' . $project->getName() . '</a>.
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
        $this->breadcrumb->add("Mon compte", $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Nouveau projet", null);

        // rendu termplate
        return $this->render('user/project/creation_projet.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'formErrors' => $formErrors ?? false
        ]);
    }


    #[Route(
        '/comptes/projets/aides/{id}-{slug}/',
        name: 'app_user_project_aides',
        requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+']
    )]
    public function aides(
        int $id,
        ProjectRepository $ProjectRepository,
        UserService $userService,
        RequestStack $requestStack,
        AidProjectRepository $aidProjectRepository,
        ManagerRegistry $managerRegistry,
        NotificationService $notificationService,
        ReferenceService $referenceService,
        SpreadsheetExporterService $spreadsheetExporterService,
        AidService $aidService,
        MessageBusInterface $bus,
        ProjectService $projectService
    ): Response {
        $projectCreated = $requestStack->getCurrentRequest()->get('projectCreated', 0);

        $project = $ProjectRepository->findOneBy(
            [
                'id' => $id
            ]
        );
        $user = $userService->getUserLogged();

        if (
            !$project instanceof Project
            || !$userService->isMemberOfOrganization($project->getOrganization(), $user)
        ) {
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
            }
            $aidParams['keyword'] = $project->getName();
            $searchParams['keyword'] = $aidParams['keyword'];

            // regarde si le projet à un projet référent associé pour écraser la recherche
            if (
                $project->getProjectReference() instanceof ProjectReference
                && $project->getProjectReference()->getName()
            ) {
                $aidParams['keyword'] = $project->getProjectReference()->getName();
                $aidParams['projectReference'] = $project->getProjectReference();
                $searchParams['keyword'] = $aidParams['keyword'];
            }

            $aidsSuggested = $aidService->searchAidsV3($aidParams);
            if (!empty($aidsSuggested)) {
                $referenceService->setHighlightedWords(null, $aidParams['keyword']);
            }
        }

        // formulaire suppression aidProject
        $formAidProjectDelete = $this->createForm(AidProjectDeleteType::class);
        $formAidProjectDelete->handleRequest($requestStack->getCurrentRequest());
        if ($formAidProjectDelete->isSubmitted()) {
            if ($formAidProjectDelete->isValid()) {
                // suppression
                $aidProject = $aidProjectRepository->find($formAidProjectDelete->get('idAidProject')->getData());
                if (
                    $aidProject instanceof AidProject
                    && $aidProject->getProject()
                    && $aidProject->getProject()->getId() == $project->getId()
                ) {
                    $managerRegistry->getManager()->remove($aidProject);
                    $managerRegistry->getManager()->flush();
                }

                if ($project->getOrganization()) {
                    foreach ($project->getOrganization()->getBeneficiairies() as $beneficiary) {
                        if ($beneficiary->getId() != $user->getId()) {
                            $notificationService->addNotification(
                                $beneficiary,
                                'Une aide a été supprimée d’un projet',
                                '<p>
                                ' . $user->getFirstname() . ' ' . $user->getLastname() . ' a supprimé une aide du projet
                                <a href="'
                                . $this->generateUrl(
                                    'app_user_project_details_fiche_projet',
                                    [
                                        'id' => $project->getId(),
                                        'slug' => $project->getSlug()
                                    ],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                )
                                . '">' . $project->getName() . '</a>.
                                </p>'
                            );
                        }
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

        // formulaire export projet
        $nbMaxAids = 10;
        $formExportProjectParams = count($project->getAidProjects()) > $nbMaxAids
            ? []
            : ['attr' => ['target' => '_blank']];
        $formExportProject = $this->createForm(ProjectExportType::class, null, $formExportProjectParams);
        if ($project->getId()) {
            $formExportProject->get('idProject')->setData($project->getId());
        }
        $formExportProject->handleRequest($requestStack->getCurrentRequest());
        if ($formExportProject->isSubmitted()) {
            if ($formExportProject->isValid()) {
                // Si plus de 10 aides, on passe par le worker
                if (count($project->getAidProjects()) > $nbMaxAids) {
                    $bus->dispatch(
                        new MsgProjectExportAids(
                            $user->getId(),
                            $project->getId(),
                            $formExportProject->get('format')->getData()
                        )
                    );
                    $this->addFlash(
                        FrontController::FLASH_SUCCESS,
                        'Votre export est en cours de traitement, vous recevrez un email dès qu\'il sera prêt.'
                    );
                } else {
                    switch ($formExportProject->get('format')->getData()) {
                        case FileService::FORMAT_CSV:
                            return $spreadsheetExporterService->exportProjectAids($project, FileService::FORMAT_CSV);
                        case FileService::FORMAT_XLSX:
                            return $spreadsheetExporterService->exportProjectAids($project, FileService::FORMAT_XLSX);
                        case FileService::FORMAT_PDF:
                            return $this->exportAidsToPdf($project, $projectService);
                        default:
                            $this->addFlash(FrontController::FLASH_ERROR, 'Format non supporté');
                    }
                }
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Erreur lors de l\'export');
                return $this->redirectToRoute('app_user_project_aides', [
                    'id' => $project->getId(),
                    'slug' => $project->getSlug(),
                ]);
            }
        }

        // fil arianne
        $this->breadcrumb->add("Mon compte", $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets", $this->generateUrl('app_user_project_structure'));
        $this->breadcrumb->add("Projet " . $project->getName(), null);

        // rendu template
        return $this->render('user/project/aides.html.twig', [
            'project' => $project,
            'aidsSuggested' => $aidsSuggested,
            'searchParams' => $searchParams,
            'formAidProjectDelete' => $formAidProjectDelete,
            'projectCreated' => $projectCreated,
            'formAidProjectEdits' => $formAidProjectEdits,
            'formAidProjectEditHasError' => $formAidProjectEditHasError,
            'formExportProject' => $formExportProject
        ]);
    }

    private function exportAidsToPdf(Project $project, ProjectService $projectService): Response
    {
        $now = new \DateTime(date('Y-m-d H:i:s'));
        $filename = 'Aides-territoires_-_' . $now->format('Y-m-d') . '_-_' . $project->getSlug() . '.pdf';

        // Récupérez le contenu du PDF
        $pdfContent = $projectService->getProjectAidsExportPdfContent($project);

        // Créez une réponse avec le contenu du PDF
        $response = new Response($pdfContent);

        // Définissez le type de contenu et le nom du fichier dans les en-têtes HTTP
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '.pdf"');

        return $response;
    }


    #[Route(
        '/comptes/projets/accepter-aide-suggeree/{id}-{slug}/{idSuggested}',
        name: 'app_user_project_accept_suggested_aid',
        requirements: [
            'id' => '[0-9]+',
            'slug' => '[a-zA-Z0-9\-_]+',
            'idSuggested' => '[0-9]+',
        ]
    )]
    public function acceptAidSuggested(
        int $id,
        string $slug,
        int $idSuggested,
        ProjectRepository $projectRepository,
        AidSuggestedAidProjectRepository $aidSuggestedAidProjectRepository,
        UserService $userService,
        NotificationService $notificationService,
        ManagerRegistry $managerRegistry
    ): RedirectResponse {
        // vérifie projet et appartenance
        $project = $projectRepository->findOneBy(
            [
                'id' => $id
            ]
        );
        $user = $userService->getUserLogged();

        if (
            !$project instanceof Project
            || !$userService->isMemberOfOrganization($project->getOrganization(), $user)
        ) {
            $this->addFlash(FrontController::FLASH_ERROR, 'Vous n\'avez pas accès à ce projet.');

            return $this->redirectToRoute('app_user_project_structure');
        }

        // charge la suggestion
        $aidSuggestedAidProject = $aidSuggestedAidProjectRepository->findOneBy(
            [
                'id' => $idSuggested
            ]
        );
        if (
            !$aidSuggestedAidProject instanceof AidSuggestedAidProject
            || $aidSuggestedAidProject->getProject() != $project
        ) {
            $this->addFlash(FrontController::FLASH_ERROR, 'Vous n\'avez pas accès à cette suggestion.');

            return $this->redirectToRoute(
                'app_user_project_aides',
                [
                    'id' => $project->getId(),
                    'slug' => $project->getSlug()
                ]
            );
        }

        // Accepte la suggestion et ajoute l'aide au projet
        $aidSuggestedAidProject->setIsAssociated(true);
        $aidSuggestedAidProject->setTimeAssociated(new \DateTime());
        $aidSuggestedAidProject->setIsRejected(false);
        $aidSuggestedAidProject->setTimeRejected(null);
        $managerRegistry->getManager()->persist($aidSuggestedAidProject);

        // ajoute au projet
        $aidProject = new AidProject();
        $aidProject->setAid($aidSuggestedAidProject->getAid());
        $aidProject->setCreator($user);
        $project->addAidProject($aidProject);
        $managerRegistry->getManager()->persist($project);

        // sauvegarde
        $managerRegistry->getManager()->flush();

        // envoi notification à tous les autres membres de l'oganisation
        if ($project->getOrganization()) {
            foreach ($project->getOrganization()->getBeneficiairies() as $beneficiary) {
                if ($beneficiary->getId() == $user->getId()) {
                    continue;
                }
                $notificationService->addNotification(
                    $beneficiary,
                    'Nouvelle aide ajoutée à un projet',
                    '<p>
                    ' . $user->getFirstname()
                    . ' '
                    . $user->getLastname()
                    . ' a ajouté une aide au projet
                    <a href="'
                    . $this->generateUrl(
                        'app_user_project_details_fiche_projet',
                        ['id' => $project->getId(), 'slug' => $project->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                        . '">' . $project->getName() . '</a>.
                    </p>'
                );
            }
        }

        // message flash
        $this->addFlash(FrontController::FLASH_SUCCESS, 'L\'aide a bien été ajoutée.');

        return $this->redirectToRoute(
            'app_user_project_aides',
            [
                'id' => $project->getId(),
                'slug' => $project->getSlug()
            ]
        );
    }

    #[Route(
        '/comptes/projets/refuser-aide-suggeree/{id}-{slug}/{idSuggested}',
        name: 'app_user_project_refuse_suggested_aid',
        requirements: [
            'id' => '[0-9]+',
            'slug' => '[a-zA-Z0-9\-_]+',
            'idSuggested' => '[0-9]+',
        ]
    )]
    public function refuseAidSuggested(
        int $id,
        string $slug,
        int $idSuggested,
        ProjectRepository $projectRepository,
        AidSuggestedAidProjectRepository $aidSuggestedAidProjectRepository,
        UserService $userService,
        ManagerRegistry $managerRegistry
    ): RedirectResponse {
        // vérifie projet et appartenance
        $project = $projectRepository->findOneBy(
            [
                'id' => $id
            ]
        );
        $user = $userService->getUserLogged();

        if (
            !$project instanceof Project
            || !$userService->isMemberOfOrganization($project->getOrganization(), $user)
        ) {
            $this->addFlash(FrontController::FLASH_ERROR, 'Vous n\'avez pas accès à ce projet.');

            return $this->redirectToRoute(
                'app_user_project_aides',
                [
                    'id' => $project->getId(),
                    'slug' => $project->getSlug()
                ]
            );
        }

        // charge la suggestion
        $aidSuggestedAidProject = $aidSuggestedAidProjectRepository->findOneBy(
            [
                'id' => $idSuggested
            ]
        );
        if (
            !$aidSuggestedAidProject instanceof AidSuggestedAidProject
            || $aidSuggestedAidProject->getProject() != $project
        ) {
                $this->addFlash(FrontController::FLASH_ERROR, 'Vous n\'avez pas accès à cette association.');
                return $this->redirectToRoute(
                    'app_user_project_aides',
                    [
                        'id' => $project->getId(),
                        'slug' => $project->getSlug()
                    ]
                );
        }

        // refuse la suggestion
        $aidSuggestedAidProject->setIsRejected(true);
        $aidSuggestedAidProject->setTimeRejected(new \DateTime());
        $aidSuggestedAidProject->setIsAssociated(false);
        $aidSuggestedAidProject->setTimeAssociated(null);
        $managerRegistry->getManager()->persist($aidSuggestedAidProject);
        $managerRegistry->getManager()->flush();

        // message flash
        $this->addFlash(FrontController::FLASH_SUCCESS, 'L\'aide a bien été refusée.');

        return $this->redirectToRoute(
            'app_user_project_aides',
            [
                'id' => $project->getId(),
                'slug' => $project->getSlug()
            ]
        );
    }

    #[Route(
        '/comptes/projets/similaires/{id}-{slug}/',
        name: 'app_user_project_similaires',
        requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+']
    )]
    public function similaires(
        int $id,
        string $slug,
        UserService $userService,
        ProjectValidatedRepository $projectValidatedRepository,
        ProjectRepository $projectRepository,
        RequestStack $requestStack,
        ReferenceService $referenceService
    ): Response {
        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);

        $project = $projectRepository->findOneBy(
            [
                'id' => $id,
                'slug' => $slug
            ]
        );
        $user = $userService->getUserLogged();

        if (!$project instanceof Project || !$userService->isMemberOfOrganization($project->getOrganization(), $user)) {
            return $this->redirectToRoute('app_user_project_structure');
        }

        // Projets subventionnés
        $synonyms = ($project->getProjectReference())
            ? $referenceService->getSynonymes($project->getProjectReference()->getName())
            : null;

        $project_perimeter = ($user->getDefaultOrganization() && $user->getDefaultOrganization()->getPerimeter())
            ? $user->getDefaultOrganization()->getPerimeter()
            : null;
        $projects = [];
        if ($project_perimeter instanceof Perimeter) {
            $projectParams = [
                'perimeter' => $project_perimeter,
                'radius' => 30,
                'maxResults' => 60
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
            if (empty($projets_publics) && $project_perimeter instanceof Perimeter) {
                $projectParams['radius'] = 300;
                $projets_publics = $projectRepository->findPublicProjects($projectParams);
            }
        }

        // fil d'arianne
        $this->breadcrumb->add("Mon compte", $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Projet " . $project->getName(), null);

        // rendu template
        return $this->render('user/project/similaires.html.twig', [
            'project' => $project,
            'projets_publics' => $projets_publics,
            'myPager' => $pagerfanta,
        ]);
    }

    #[Route('/comptes/projets/ajax-lock/', name: 'app_user_project_ajax_lock', options: ['expose' => true])]
    public function ajaxLock(
        RequestStack $requestStack,
        ProjectRepository $projectRepository,
        ProjectService $projectService,
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

            // charge projet
            $project = $projectRepository->find($id);
            if (!$project instanceof Project) {
                throw new \Exception('Projet manquant');
            }

            // verifie que le user peut lock
            $canLock = $projectService->canUserLock($project, $user);
            if (!$canLock) {
                throw new \Exception('Vous ne pouvez pas bloquer ce projet');
            }

            // regarde si deja lock
            $isLockedByAnother = $projectService->isLockedByAnother($project, $user);
            if ($isLockedByAnother) {
                throw new \Exception('Projet déjà bloqué');
            }

            // la débloque
            $projectService->lock($project, $user);

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

    #[Route('/comptes/projets/{id}/unlock/', name: 'app_user_project_unlock', requirements: ['id' => '\d+'])]
    public function unlock(
        int $id,
        ProjectRepository $projectRepository,
        UserService $userService,
        ProjectService $projectService
    ): Response {
        try {
            // le user
            $user = $userService->getUserLogged();
            if (!$user instanceof User) {
                throw new \Exception('User invalid');
            }

            // le projet
            $project = $projectRepository->find($id);
            if (!$project instanceof Project) {
                throw new \Exception('Projet invalide');
            }

            // verifie que le user peut lock
            $canLock = $projectService->canUserLock($project, $user);
            if (!$canLock) {
                throw new \Exception('Vous ne pouvez pas bloquer ce projet');
            }

            // suppression du lock
            $projectService->unlock($project);

            // message
            $this->addFlash(FrontController::FLASH_SUCCESS, 'Projet débloqué');

            // retour
            return $this->redirectToRoute('app_user_project_structure');
        } catch (\Exception $e) {
            // message
            $this->addFlash(FrontController::FLASH_ERROR, 'Impossible de débloquer le projet');

            // retour
            if (isset($project) && $project instanceof Project) {
                return $this->redirectToRoute(
                    'app_user_project_details_fiche_projet',
                    ['id' => $project->getId(), 'slug' => $project->getSlug()]
                );
            } else {
                return $this->redirectToRoute('app_user_project_structure');
            }
        }
    }

    #[Route('/comptes/projets/ajax-unlock/', name: 'app_user_project_ajax_unlock', options: ['expose' => true])]
    public function ajaxUnlock(
        RequestStack $requestStack,
        ProjectRepository $projectRepository,
        ProjectService $projectService,
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

            // charge projet
            $project = $projectRepository->find($id);
            if (!$project instanceof Project) {
                throw new \Exception('Projet manquant');
            }

            // verifie que le user peut lock
            $canLock = $projectService->canUserLock($project, $user);
            if (!$canLock) {
                throw new \Exception('Vous ne pouvez pas débloquer ce projet');
            }

            // la débloque
            $projectService->unlock($project);

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
