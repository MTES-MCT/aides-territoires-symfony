<?php

namespace App\Controller\Project;

use App\Controller\FrontController;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Aid\AidSuggestedAidProject;
use App\Entity\Project\Project;
use App\Entity\Reference\ProjectReference;
use App\Form\Program\CountySelectType;
use App\Form\Project\AddToFavoriteType;
use App\Form\Project\AidSuggestedType;
use App\Form\Project\ProjectPublicSearchType;
use App\Form\Project\ProjectValidatedSearchType;
use App\Repository\Perimeter\PerimeterRepository;
use App\Form\Project\RemoveFromFavoriteType;
use App\Repository\Aid\AidRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\Project\ProjectValidatedRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Service\Log\LogService;
use App\Service\Project\ProjectService;
use App\Service\Reference\ReferenceService;
use App\Service\Various\StringService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Pagerfanta;

class ProjectController extends FrontController
{
    
    const NB_PROJECT_BY_PAGE = 18;


    #[Route('/projets/projets-publics/', name: 'app_project_project_public')]
    public function public(
        ProjectRepository $projectRepository,
        ProjectReferenceRepository $projectReferenceRepository,
        RequestStack $requestStack,
        LogService $logService,
        UserService $userService,
        ReferenceService $referenceService,
        ProjectService $projectService
    ): Response
    {
        // le user
        $user = $userService->getUserLogged();

        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);
        
        $projectsParams = [];
        // formulaire de recherche
        $formProjectSearch = $this->createForm(ProjectPublicSearchType::class, null, ['method' => 'GET']);
        $formProjectSearch->handleRequest($requestStack->getCurrentRequest());
        if ($formProjectSearch->isSubmitted()) {
            if ($formProjectSearch->isValid()) {
                if($formProjectSearch->get('step')->getData()){
                    $projectsParams['step'] = $formProjectSearch->get('step')->getData();
                }
                if($formProjectSearch->get('perimeter')->getData()){
                    $projectsParams['perimeter'] = $formProjectSearch->get('perimeter')->getData();
                }
                if($formProjectSearch->get('contractLink')->getData()){
                    $projectsParams['contractLink'] = $formProjectSearch->get('contractLink')->getData();
                }
                if($formProjectSearch->get('name')->getData()){
                    $projectsParams['search'] = $formProjectSearch->get('name')->getData();
                    $projectsParams = array_merge($projectsParams, $referenceService->getSynonymes($formProjectSearch->get('name')->getData()));
                }
                $projectRerence = $projectReferenceRepository->findOneBy(['name' => $formProjectSearch->get('name')->getData()]);
                if ($projectRerence instanceof ProjectReference) {
                    $projectsParams['projectReference'] = $projectRerence;
                }
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Une erreur est survenue lors de la recherche de projets publics.');
            }
        }

        // parametres recherche
        $projectsParams['isPublic'] = true;
        $projectsParams['status'] = Project::STATUS_PUBLISHED;
        $projectsParams['orderBy'] = [
            'sort' => 'p.timeCreate',
            'order' => 'DESC'
        ];

        $projects = $projectService->searchProjects($projectsParams);
        
         // le paginateur
        $adapter = new ArrayAdapter($projects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_PROJECT_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        if ($formProjectSearch->isSubmitted() && $formProjectSearch->isValid()) {
            // Log recherche
            $logService->log(
                type: LogService::PROJECT_PUBLIC_SEARCH,
                params: [
                    'querystring' => parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null,
                    'resultsCount' => $pagerfanta->getNbResults(),
                    'perimeter' => (isset($projectsParams['perimeter']) && $projectsParams['perimeter'] instanceof Perimeter) ? $projectsParams['perimeter'] : null,
                    'user' => $user,
                    'organization' => ($user && $user->getDefaultOrganization()) ? $user->getDefaultOrganization() : null
                ],
            );
        }
                
        // fil arianne
        $this->breadcrumb->add(
            ' Projets publics',
            null
        );

        // rendu template
        return $this->render('project/project/public.html.twig', [
            'my_pager' => $pagerfanta,
            'formProjectSearch' => $formProjectSearch
        ]);
    }

    #[Route('/projets/projets-publics/{id}-{slug}/', name: 'app_project_project_public_details', requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+'])]
    public function publicDetails(
        $id,
        $slug,
        ProjectRepository $projectRepository,
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        AidRepository $aidRepository,
        LogService $logService
    ): Response
    {
        // le projet
        $project = $projectRepository->findOneBy(
            [
                'id' => $id,
                'slug' => $slug,
                'isPublic' => true
            ]
        );
        if (!$project instanceof Project) {
            return $this->redirectToRoute('app_project_project_public');
        }

        // le user
        $user = $userService->getUserLogged();

        // formulaire ajouter aux favoris
        $formAddToFavorite = $this->createForm(AddToFavoriteType::class);
        $formAddToFavorite->handleRequest($requestStack->getCurrentRequest());
        if ($formAddToFavorite->isSubmitted()) {
            if ($formAddToFavorite->isValid()) {
                if ($user->getDefaultOrganization()) {
                    $user->getDefaultOrganization()->addFavoriteProject($project);
                    $managerRegistry->getManager()->persist($user->getDefaultOrganization());
                    $managerRegistry->getManager()->flush();

                    $this->tAddFlash(
                        FrontController::FLASH_SUCCESS,
                        'Le projet «'.$project->getName().'» a bien été ajouté à <a href="'.$this->generateUrl('app_user_project_favoris').'">vos projets favoris</a>.'
                    );
                } else {
                    $this->tAddFlash(
                        FrontController::FLASH_ERROR,
                        'Vous devez appartenir à une organisation pour ajouter un projet à vos favoris.'
                    );

                    return $this->redirectToRoute('app_project_project_public_details', ['id' => $project->getId(), 'slug' => $project->getSlug()]);
                }
            }
        }

        // formulaire pour retirer des favoris
        $formRemoveFromFavorite = $this->createForm(RemoveFromFavoriteType::class);
        $formRemoveFromFavorite->handleRequest($requestStack->getCurrentRequest());
        if ($formRemoveFromFavorite->isSubmitted()) {
            if ($formRemoveFromFavorite->isValid()) {
                if ($user->getDefaultOrganization()) {
                    $user->getDefaultOrganization()->removeFavoriteProject($project);
                    $managerRegistry->getManager()->persist($user->getDefaultOrganization());
                    $managerRegistry->getManager()->flush();

                    $this->tAddFlash(
                        FrontController::FLASH_SUCCESS,
                        'Le projet «'.$project->getName().'» a bien été retiré de <a href="'.$this->generateUrl('app_user_project_favoris').'">vos projets favoris</a>.'
                    );
                }
            }
        }

        // formulaire suggerer une aide
        $fromSuggestAid = $this->createForm(AidSuggestedType::class);
        $fromSuggestAid->handleRequest($requestStack->getCurrentRequest());
        if ($fromSuggestAid->isSubmitted()) {
            if ($fromSuggestAid->isValid()) {
                try {
                    $aidSuggested = new AidSuggestedAidProject();
                    $url = $fromSuggestAid->get('aidUrl')->getData();
                    preg_match('/.*\/aides\/(.*)\//', $url, $matches);
                    $slug = $matches[1] ?? null;
                    if (!$slug) {
                        throw new \Exception('Impossible de trouver le slug de l\'aide');
                    }

                    $aidSuggested->setAid(
                        $aidRepository->findOneCustom(
                            [
                                'showInSearch' => true,
                                'slug' => $slug
                            ]
                        )
                    );
                    $aidSuggested->setCreator($user ?? null);
                    $aidSuggested->setProject($project);
                    $managerRegistry->getManager()->persist($aidSuggested);
                    $managerRegistry->getManager()->flush();

                    $this->addFlash(
                        FrontController::FLASH_SUCCESS,
                        'Merci! L’aide a bien été suggérée!'
                    );
                } catch (\Exception $e) {
                    $this->addFlash(
                        FrontController::FLASH_ERROR,
                        'Impossible de suggérer l\'aide'
                    );
                }
            } else {
                $fromSuggestAidInvalid = true;
            }
        }

        // log
        $logService->log(
            type: LogService::PROJECT_PUBLIC_VIEW,
            params: [
                'project' => $project,
                'organization' => $userService->getUserLogged() ? $userService->getUserLogged()->getDefaultOrganization() : null,
                'user' => $userService->getUserLogged(),
            ]
        );

        // les projets favoris de l'utilisateur
        $favoriteProjectDTOs = [];
        if ($user->getDefaultOrganization()) {
            $favoriteProjectDTOs = $user->getDefaultOrganization()->getFavoriteProjects();
        }

        // fil arianne
        $this->breadcrumb->add(
            'Projets publics',
            $this->generateUrl('app_project_project_public')
        );

        $this->breadcrumb->add(
            $project->getName(),
            null
        );

        // rendu template
        return $this->render('project/project/public-details.html.twig', [
            'project' => $project,
            'formAddToFavorite' => $formAddToFavorite->createView(),
            'formRemoveFromFavorite' => $formRemoveFromFavorite->createView(),
            'fromSuggestAid' => $fromSuggestAid->createView(),
            'fromSuggestAidInvalid' => $fromSuggestAidInvalid ?? null,
            'favoriteProjects' => $favoriteProjectDTOs
        ]);
    }

    #[Route('/projets/projets-subventionnes/', name: 'app_project_project_subsidized')]
    public function subsidized(
        ProjectValidatedRepository $projectValidatedRepository,
        RequestStack $requestStack,
        StringService $stringService,
        PerimeterRepository $perimeterRepository
    ) : Response
    {

        $projects_count = $projectValidatedRepository->count([]);

        $formProjectSearch = $this->createForm(ProjectValidatedSearchType::class, null, [
            'method'=>'GET',
            'action'=>$this->generateUrl('app_project_project_subsidized_detail')
        ]);

        $formProjectSearch->handleRequest($requestStack->getCurrentRequest());

         // formulaire choix département
        $formCounties = $this->createForm(CountySelectType::class,null,['method'=>'GET','action'=>$this->generateUrl('app_project_project_subsidized_detail')]);


        // les infos départements pour la carte
        $counties = $perimeterRepository->findCounties();
        $departmentsData = [];
        foreach ($counties as $county) {
            $departmentsData[] = [
                'code' => $county->getCode(),
                'name' => $county->getName(),
                'id' => $county->getId(),
                'projects_count' => $projectValidatedRepository->countProjectInCounty(['id' => $county->getId()])
            ];
        }


        // fil arianne
        $this->breadcrumb->add(
            'Projets subventionnés',
            null
        );

        return $this->render('project/project/subsidized.html.twig', [
            'projects_count' => $projects_count,
            'formProjectSearch' => $formProjectSearch,
            'formCounties' => $formCounties,
            'departmentsData' => $departmentsData
        ]);
    }

    #[Route('/projets/projets-subventionnes/resultats/', name: 'app_project_project_subsidized_detail')]
    public function subsidizedDetail(
        PerimeterRepository $perimeterRepository,
        RequestStack $requestStack,
        ProjectValidatedRepository $projectValidatedRepository,
        LogService $logService,
        UserService $userService
    ) : Response
    {
        // le user
        $user = $userService->getUserLogged();
        
        // parametres et variables
        $projects=[];$commune_search=false;$keyword=null;
        $department_search = false;
        $idPerimeter = $requestStack->getCurrentRequest()->query->get('project_perimeter', false);

        // formulaire recherche
        $formProjectSearch = $this->createForm(ProjectValidatedSearchType::class,null,[
            'method'=>'GET'
            ]
        );
        $formProjectSearch->handleRequest($requestStack->getCurrentRequest());
        if ($formProjectSearch->isSubmitted()) {
            if ($formProjectSearch->isValid()) {
                if($formProjectSearch->get('project_perimeter')->getData()){
                    $project_perimeter = $formProjectSearch->get('project_perimeter')->getData();
                }
                if($formProjectSearch->get('text')->getData()){
                    $keyword = $formProjectSearch->get('text')->getData();
                }

                $projects = $projectValidatedRepository->findProjectInRadius(
                    [
                    'perimeter' => $project_perimeter,
                    'search' => $keyword,
                    'radius' => 30
                    ]
                );

                $commune_search=true;
            }
        }

        // formulaire par département
        $formCounties = $this->createForm(CountySelectType::class,null,[
            'method'=>'GET'
            ]
        );
        $formCounties->handleRequest($requestStack->getCurrentRequest());
        if ($formCounties->isSubmitted()) {
            if ($formCounties->isValid()) {
                $project_perimeter = $perimeterRepository->findOneBy(['code' => $formCounties->get('county')->getData()]);
                if ($project_perimeter instanceof Perimeter) {
                    $projects=$projectValidatedRepository->findProjectInCounty(
                        ['id' => $project_perimeter->getId()]
                    );
                    $department_search=true;
                }
            }
        }

        if ($idPerimeter && !isset($project_perimeter)) {
            $project_perimeter = $perimeterRepository->find($idPerimeter);
            if ($project_perimeter instanceof Perimeter) {
                $projects=$projectValidatedRepository->findProjectInCounty(
                    ['id' => $project_perimeter->getId()]
                );
                $department_search=true;
            }
        }

        // Log recherche
        $logService->log(
            type: LogService::PROJECT_VALIDATED_SEARCH,
            params: [
                'search' => (isset($keyword)) ? $keyword : null,
                'querystring' => parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null,
                'resultsCount' => (isset($projects) && is_array($projects)) ? count($projects) : 0,
                'perimeter' => (isset($project_perimeter) && $project_perimeter instanceof Perimeter) ? $project_perimeter : null,
                'user' => $user,
                'organization' => ($user && $user->getDefaultOrganization()) ? $user->getDefaultOrganization() : null
            ],
        );
        
        // fil arianne
        $this->breadcrumb->add(
            'Projets subventionnés',
            $this->generateUrl('app_project_project_subsidized')
        );

        $this->breadcrumb->add(
            'Résutats de votre recherche de projets subventionnés',
            null
        );


        return $this->render('project/project/subsidized-detail.html.twig', [
            'commune_search' => $commune_search,
            'department_search' => $department_search,
            'project_perimeter' => $project_perimeter ?? null,
            'formProjectSearch' => $formProjectSearch,
            'projects' => $projects
        ]);
    }
}
