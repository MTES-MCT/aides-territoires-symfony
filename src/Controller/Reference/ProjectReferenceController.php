<?php

namespace App\Controller\Reference;

use App\Controller\FrontController;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Organization\OrganizationType;
use App\Entity\Reference\ProjectReference;
use App\Form\Reference\ProjectReferenceSearchType;
use App\Repository\Aid\AidRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\Project\ProjectValidatedRepository;
use App\Repository\Reference\KeywordReferenceRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Service\Aid\AidService;
use App\Service\Reference\ReferenceService;
use App\Service\Various\StringService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Route(priority: 5)]
class ProjectReferenceController extends FrontController
{
    
    const NB_PAID_BY_PAGE = 30;
    const NB_PROJECT_BY_PAGE = 18;


    #[Route('/projets-references/', name: 'app_project_reference')]
    public function index(
        AidRepository $aidRepository,
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        ProjectReferenceRepository $projectReferenceRepository,
        AidService $aidService
    ): Response
    {
        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);

        // si on a un slug projet référent en query
        $prSlug = $requestStack->getCurrentRequest()->get('prSlug', null);
        if ($prSlug) {
            $projectReferenceForce = $projectReferenceRepository->findOneBy([
                'slug' => $prSlug
            ]);
            if ($projectReferenceForce instanceof ProjectReference) {
                $requestStack->getCurrentRequest()->getSession()->set('reference_name', $projectReferenceForce->getName());
            }
        }

        // paramètres en session
        $organizationType = $requestStack->getCurrentRequest()->getSession()->get('reference_organizationType', null);
        $name = $requestStack->getCurrentRequest()->getSession()->get('reference_name', null);
        $perimeter = $requestStack->getCurrentRequest()->getSession()->get('reference_perimeter', null);

        // Pour éviter l'erreur sur le formulaire
        if ($perimeter instanceof Perimeter) {
            $managerRegistry->getManager()->persist($perimeter);
        }
        // le formulaire
        $formProjectReferenceSearch = $this->createForm(ProjectReferenceSearchType::class, null, [
            'forceOrganizationType' => $organizationType,
            'forcePerimeter' => $perimeter,
            'forceName' => $name
        ]);
        $formProjectReferenceSearch->handleRequest($requestStack->getCurrentRequest());
        if ($formProjectReferenceSearch->isSubmitted()) {
            if ($formProjectReferenceSearch->isValid()) {          
                // créer la session avec les valeurs du formulaire     
                if ($formProjectReferenceSearch->has('organizationType')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_organizationType', $formProjectReferenceSearch->get('organizationType')->getData());
                    $organizationType = $formProjectReferenceSearch->get('organizationType')->getData();
                }
                if ($formProjectReferenceSearch->has('perimeter')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_perimeter', $formProjectReferenceSearch->get('perimeter')->getData());
                    $perimeter = $formProjectReferenceSearch->get('perimeter')->getData();
                }
                if ($formProjectReferenceSearch->has('name')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_name', $formProjectReferenceSearch->get('name')->getData());
                    $name = $formProjectReferenceSearch->get('name')->getData();
                }
            }
        }

        // recupère les synonymes du nom
        $aidParams = [
            'showInSearch' => true,
        ];
        $organizationType = $requestStack->getCurrentRequest()->getSession()->get('reference_organizationType', null);
        if ($organizationType instanceof OrganizationType) {
            $aidParams['organizationType'] = $organizationType;
        }
        if ($perimeter instanceof Perimeter) {
            $aidParams['perimeterFrom'] = $perimeter;
        }
        $aids = [];
        if ($name) {
            // $aids = $aidRepository->findAidsBySynonyms(array_merge($aidParams, $referenceService->getSynonymes($name)));
            $aids = $aidRepository->findCustom(array_merge($aidParams, ['keyword' => $name]));
            $aids = $aidService->postPopulateAids($aids, $aidParams);
        }

         // le paginateur
        try {
        $adapter = new ArrayAdapter($aids);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_PAID_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);
        } catch (OutOfRangeCurrentPageException $e) {
            $this->addFlash(
                FrontController::FLASH_ERROR,
                'Le numéro de page demandé n\'existe pas'
            );
            $newUrl = preg_replace('/(page=)[^\&]+/', 'page=' . $pagerfanta->getNbPages(), $requestStack->getCurrentRequest()->getRequestUri());
            return new RedirectResponse($newUrl);
        }

        // fil arianne
        $this->breadcrumb->add(
            'Projets',
            null
        );

        return $this->render('reference/index.html.twig', [
            'myPager' => $pagerfanta,
            'formProjectReferenceSearch' => $formProjectReferenceSearch,
            'forceDisplayAidAsList' => true
        ]);
    }

    #[Route('/projets-references/projets-subventionnes', name: 'app_project_reference_projet_subventionnes')]
    public function projetsSubventionnes(
        RequestStack $requestStack,
        ReferenceService $referenceService,
        ManagerRegistry $managerRegistry,
        ProjectValidatedRepository $projectValidatedRepository
    ): Response
    {
        // paramètres en session
        $organizationType = $requestStack->getCurrentRequest()->getSession()->get('reference_organizationType', $managerRegistry->getRepository(OrganizationType::class)->findOneBy(['slug' => OrganizationType::SLUG_COMMUNE]));
        $name = $requestStack->getCurrentRequest()->getSession()->get('reference_name', null);
        $perimeter = $requestStack->getCurrentRequest()->getSession()->get('reference_perimeter', null);

        // Pour éviter l'erreur sur le formulaire
        if ($perimeter instanceof Perimeter) {
            $managerRegistry->getManager()->persist($perimeter);
        }

        // le formulaire
        $formProjectReferenceSearch = $this->createForm(ProjectReferenceSearchType::class, null, [
            'forceOrganizationType' => $organizationType,
            'forcePerimeter' => $perimeter,
            'forceName' => $name
        ]);
        $formProjectReferenceSearch->handleRequest($requestStack->getCurrentRequest());
        if ($formProjectReferenceSearch->isSubmitted()) {
            if ($formProjectReferenceSearch->isValid()) {          
                // créer la session avec les valeurs du formulaire     
                if ($formProjectReferenceSearch->has('organizationType')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_organizationType', $formProjectReferenceSearch->get('organizationType')->getData());
                    $organizationType = $formProjectReferenceSearch->get('organizationType')->getData();
                }
                if ($formProjectReferenceSearch->has('perimeter')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_perimeter', $formProjectReferenceSearch->get('perimeter')->getData());
                    $perimeter = $formProjectReferenceSearch->get('perimeter')->getData();
                }
                if ($formProjectReferenceSearch->has('name')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_name', $formProjectReferenceSearch->get('name')->getData());
                    $name = $formProjectReferenceSearch->get('name')->getData();
                }
            }
        }

        // chargement des projets subventionnes
        $projectValidatedsParams = [];
        if ($name) {
            $projectValidatedsParams = $referenceService->getSynonymes($name);
        }
        $projectValidatedsParams = array_merge($projectValidatedsParams, [
            'organizationType' => $organizationType,
            'perimeter' => $perimeter,
            'radius' => 30
        ]);

        if ($organizationType && $name) {
            $projectValidateds = $projectValidatedRepository->findCustom($projectValidatedsParams);
        } else {
            $projectValidateds = [];
        }
        

        // fil arianne
        $this->breadcrumb->add(
            'Projets',
            null
        );

        // rendu templates
        return $this->render('reference/projets_subventionnes.html.twig', [
            'projectValidateds' => $projectValidateds,
            'formProjectReferenceSearch' => $formProjectReferenceSearch,
            'commune_search' => true,
            'project_perimeter' => $perimeter
        ]);
    }

    #[Route('/projets-references/projets-publics', name: 'app_project_reference_projects_publics')]
    public function projectsPublics(
        RequestStack $requestStack,
        ReferenceService $referenceService,
        ManagerRegistry $managerRegistry,
        ProjectRepository $projectRepository
    ): Response
    {
        // paramètres en session
        $organizationType = $requestStack->getCurrentRequest()->getSession()->get('reference_organizationType', null);
        $name = $requestStack->getCurrentRequest()->getSession()->get('reference_name', null);
        $perimeter = $requestStack->getCurrentRequest()->getSession()->get('reference_perimeter', null);

        // Pour éviter l'erreur sur le formulaire
        if ($perimeter instanceof Perimeter) {
            $managerRegistry->getManager()->persist($perimeter);
        }
        // le formulaire
        $formProjectReferenceSearch = $this->createForm(ProjectReferenceSearchType::class, null, [
            'forceOrganizationType' => $organizationType,
            'forcePerimeter' => $perimeter,
            'forceName' => $name
        ]);
        $formProjectReferenceSearch->handleRequest($requestStack->getCurrentRequest());
        if ($formProjectReferenceSearch->isSubmitted()) {
            if ($formProjectReferenceSearch->isValid()) {          
                // créer la session avec les valeurs du formulaire     
                if ($formProjectReferenceSearch->has('organizationType')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_organizationType', $formProjectReferenceSearch->get('organizationType')->getData());
                    $organizationType = $formProjectReferenceSearch->get('organizationType')->getData();
                }
                if ($formProjectReferenceSearch->has('perimeter')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_perimeter', $formProjectReferenceSearch->get('perimeter')->getData());
                    $perimeter = $formProjectReferenceSearch->get('perimeter')->getData();
                }
                if ($formProjectReferenceSearch->has('name')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_name', $formProjectReferenceSearch->get('name')->getData());
                    $name = $formProjectReferenceSearch->get('name')->getData();
                }
            }
        }

        // chargement des projets publics
        $projectsParams = [];
        if ($name) {
            $projectsParams['search'] = $name;
        }
        if ($organizationType) {
            $projectsParams['organizationType'] = $organizationType;
        }
        if ($perimeter) {
            $projectsParams['perimeter'] = $perimeter;
        }
        $projectsParams = array_merge($projectsParams, [
            'limit' => null,
            'orderBy' => [
                'sort' => 'p.timeCreate',
                'order' => 'DESC'
            ]
        ]);
        
        $projectsPublics = $projectRepository->findPublicProjects($projectsParams);

        // fil arianne
        $this->breadcrumb->add(
            'Projets',
            null
        );

        // rendu templates
        return $this->render('reference/projects_publics.html.twig', [
            'projectsPublics' => $projectsPublics,
            'formProjectReferenceSearch' => $formProjectReferenceSearch,
            'commune_search' => true,
            'project_perimeter' => $perimeter
        ]);
    }

    #[Route('/projets-references/similaires', name: 'app_project_reference_similar')]
    public function similar(
        ProjectReferenceRepository $projectReferenceRepository,
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        StringService $stringService
    ) : Response
    {
        // paramètres en session
        $organizationType = $requestStack->getCurrentRequest()->getSession()->get('reference_organizationType', null);
        $name = $requestStack->getCurrentRequest()->getSession()->get('reference_name', null);
        $perimeter = $requestStack->getCurrentRequest()->getSession()->get('reference_perimeter', null);

        // Pour éviter l'erreur sur le formulaire
        if ($perimeter instanceof Perimeter) {
            $managerRegistry->getManager()->persist($perimeter);
        }
        // le formulaire
        $formProjectReferenceSearch = $this->createForm(ProjectReferenceSearchType::class, null, [
            'forceOrganizationType' => $organizationType,
            'forcePerimeter' => $perimeter,
            'forceName' => $name
        ]);
        $formProjectReferenceSearch->handleRequest($requestStack->getCurrentRequest());
        if ($formProjectReferenceSearch->isSubmitted()) {
            if ($formProjectReferenceSearch->isValid()) {          
                // créer la session avec les valeurs du formulaire     
                if ($formProjectReferenceSearch->has('organizationType')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_organizationType', $formProjectReferenceSearch->get('organizationType')->getData());
                    $organizationType = $formProjectReferenceSearch->get('organizationType')->getData();
                }
                if ($formProjectReferenceSearch->has('perimeter')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_perimeter', $formProjectReferenceSearch->get('perimeter')->getData());
                    $perimeter = $formProjectReferenceSearch->get('perimeter')->getData();
                }
                if ($formProjectReferenceSearch->has('name')) {
                    $requestStack->getCurrentRequest()->getSession()->set('reference_name', $formProjectReferenceSearch->get('name')->getData());
                    $name = $formProjectReferenceSearch->get('name')->getData();
                }
            }
        }

        // regarde si la recherche corresponds exacement à un projet référent
        $projectReference = $projectReferenceRepository->findOneBy([
            'name' => $name
        ]);

        // Tous les projets référents
        $params = [
            'orderBy' => [
                'sort' => 'projectReferenceCategory.name',
                'order' => 'ASC'
            ],
            'addOrderBy' => [
                [
                    'sort' => 'pr.name',
                    'order' => 'ASC'
                ]
            ]
        ];
        if ($projectReference instanceof ProjectReference) {
            $params['excludes'] = [$projectReference];
        }
        $projectReferences = $projectReferenceRepository->findCustom($params);
                
        $closes = [];
        $others = [];
        foreach ($projectReferences as $projectReferenceCurrent) {
            if (
                $projectReference instanceof ProjectReference
                && $projectReference->getProjectReferenceCategory()->getId() == $projectReferenceCurrent->getProjectReferenceCategory()->getId()
            ) {
                $closes[] = $projectReferenceCurrent;
            } else {
                $others[] = $projectReferenceCurrent;
            }
        }

        // fil arianne
        $this->breadcrumb->add(
            'Projets',
            null
        );

        // rendu templates
        return $this->render('reference/similar.html.twig', [
            'closes' => $closes,
            'others' => $others,
            'formProjectReferenceSearch' => $formProjectReferenceSearch,
        ]);
    }

    #[Route('/projets-references/ajax-ux-autocomplete/', name: 'app_project_reference_ajax_ux_autocomplete')]
    public function ajaxUxAutocomplete(
        ProjectReferenceRepository $projectReferenceRepository,
        KeywordReferenceRepository $keywordReferenceRepository,
        RequestStack $requestStack
    ): JsonResponse
    {
        $query = $requestStack->getCurrentRequest()->get('query', null);

        // recherche les projets référents correspondants
        $projectReferences = $projectReferenceRepository->findCustom([
            'nameLike' => $query,
            'orderBy' => [
                'sort' => 'pr.name',
                'order' => 'ASC'
            ]
        ]);

        $results = [];
        foreach ($projectReferences as $projectReference) {
            $results[] = [
                'value' => $projectReference->getName(),
                'text' => $projectReference->getName()
            ];
        }

        // recherche les mots clés référents correspondants
        $keywordReferences = $keywordReferenceRepository->findCustom([
            'name' => $query,
            'orderBy' => [
                'sort' => 'kr.name',
                'order' => 'ASC'
            ]
        ]);

        $parents = new ArrayCollection();
        foreach ($keywordReferences as $keywordReference) {
            if (!$parents->contains($keywordReference->getParent())) {
                $parents->add($keywordReference->getParent());
            }
        }
        foreach ($parents as $parent) {
            if ($parent) {
                if ($parent->getName() != $query) {
                    $text = $parent->getName(). ', '.$query.' et synonymes';
                } else {
                    $text = $parent->getName(). ' et synonymes';
                }
                $results[] = [
                    'value' => $parent->getName(),
                    'text' => $text
                ];
            }
        }

        $return = [
            'results' => $results
        ];

        return new JsonResponse($return);
    }
}
