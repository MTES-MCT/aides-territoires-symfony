<?php

namespace App\Controller\Portal;

use App\Controller\Aid\AidController;
use App\Controller\FrontController;
use App\Entity\Alert\Alert;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Form\Aid\AidSearchType;
use App\Form\Alert\AlertCreateType;
use App\Repository\Aid\AidRepository;
use App\Repository\Portal\PortalRepository;
use App\Repository\Search\SearchPageRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Log\LogService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PortalController extends FrontController
{
    // c'est une page custom
    #[Route('/portails/', name: 'app_portal_portal')]
    // public function index(): Response
    // {
    //     return $this->render('portal/portal/index.html.twig', [
    //         'controller_name' => 'PortalController',
    //     ]);
    // }

    #[Route('/portails/{slug}/', name: 'app_portal_portal_details')]
    public function details(
        $slug,
        SearchPageRepository $searchPageRepository,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService,
        RequestStack $requestStack,
        AidRepository $aidRepository,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        LogService $logService
    ): Response
    {

        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);

        $user = $userService->getUserLogged();

        // charge le portail
        $search_page = $searchPageRepository->findOneBy(
            [
                'slug' => $slug
            ]
            );
        if (!$search_page instanceof SearchPage) {
            return $this->redirectToRoute('app_portal_portal');
        }
;
        // converti la querystring en parametres
        $aidParams = [
            'showInSearch' => true,
        ];
        $queryString = null;
        try {
            // certaines pages ont un querystring avec https://... d'autres directement les parametres
            $query = parse_url($search_page->getSearchQuerystring())['query'] ?? null;
            $queryString = $query ?? $search_page->getSearchQuerystring();

            $aidParams = array_merge($aidParams, $aidSearchFormService->convertQuerystringToParams($queryString));
            
        } catch (\Exception $e) {
        }

         // form recherche d'aide
        $formAidSearchParams = [
            'method' => 'GET',
            'action' => '#aid-list',
            'extended' => true,
            'forceOrganizationType' => null,
            'dontUseUserPerimeter' => true
        ];

        // parametre selon url
        $formAidSearchParams = array_merge(
            $formAidSearchParams,
            $aidSearchFormService->completeFormAidSearchParams($queryString)
        );
        // formulaire recherche aides
        $formAidSearch = $this->createForm(
            AidSearchType::class,
            null,
            $formAidSearchParams
        );
        $formAidSearch->handleRequest($requestStack->getCurrentRequest());

        // check si on affiche ou pas le formulaire étendu
        $showExtended = $aidSearchFormService->setShowExtended($formAidSearch);

        // parametres pour requetes aides
        $aidParams = array_merge($aidParams, $aidSearchFormService->completeAidParams($formAidSearch));
        // transforme le orderBy
        $aidParams = $aidSearchFormService->handleOrderBy($aidParams);

        // le paginateur
        $aids = $aidRepository->findCustom($aidParams);
        $aidParams['searchPage'] = $search_page;
        $aids = $aidService->postPopulateAids($aids, $aidParams);
        $adapter = new ArrayAdapter($aids);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(AidController::NB_AID_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        // Log recherche
        $logParams = [
            'organizationTypes' => (isset($aidParams['organizationType'])) ? [$aidParams['organizationType']] : null,
            'querystring' => $querystring ?? null,
            'resultsCount' => $pagerfanta->getNbResults(),
            'host' => $requestStack->getCurrentRequest()->getHost(),
            'perimeter' => $aidParams['perimeter'] ?? null,
            'search' => $aidParams['keyword'] ?? null,
            'organization' => ($user instanceof User && $user->getDefaultOrganization()) ? $user->getDefaultOrganization() : null,
            'backers' => $aidParams['backers'] ?? null,
            'categories' => $aidParams['categories'] ?? null,
            'programs' => $aidParams['programs'] ?? null,
        ];
        $themes = new ArrayCollection();
        if (isset($aidParams['categories']) && is_array($aidParams['categories'])) {
            foreach ($aidParams['categories'] as $category) {
                if (!$themes->contains($category->getCategoryTheme())) {
                    $themes->add($category->getCategoryTheme());
                }
            }
        }
        $logParams['themes'] = $themes->toArray();
        $logService->log(
            type: LogService::AID_SEARCH,
            params: $logParams,
        );
        
        // fil arianne
        $this->breadcrumb->add(
            'Portails',
            $this->generateUrl('app_portal_portal')
        );
        $this->breadcrumb->add(
            $search_page->getName(),
            null
        );

        // formulaire creer alerte
        $alert = new Alert();
        $formAlertCreate = $this->createForm(AlertCreateType::class, $alert);
        $formAlertCreate->handleRequest($requestStack->getCurrentRequest());
        if ($formAlertCreate->isSubmitted()) {
            if ($formAlertCreate->isValid()) {
                $user = $userService->getUserLogged();
                if ($user) {
                    try {
                        $alert->setEmail($user->getEmail());
                        $alertlQueryString = $requestStack->getCurrentRequest()->server->get('QUERY_STRING', null);
                        if (!$alertlQueryString) {
                            $alertlQueryString = $queryString;
                        }
                        $alert->setQuerystring($alertlQueryString);
                        $alert->setSource(Alert::SOURCE_AIDES_TERRITOIRES);
    
                        $managerRegistry->getManager()->persist($alert);
                        $managerRegistry->getManager()->flush();

                        $this->addFlash(
                            FrontController::FLASH_SUCCESS,
                            'Votre alerte a bien été créée'
                        );
                    } catch (\Exception $e) {
                        $this->addFlash(
                            FrontController::FLASH_ERROR,
                            'Une erreur est survenue lors de la création de votre alerte'
                        );
                    }
                }
            }
        }

        // pour les stats
        $categoriesName = [];
        if (isset($aidParams['categories']) && is_array($aidParams['categories'])) {
            foreach ($aidParams['categories'] as $category) {
                $categoriesName[] = $category->getName();
            }
        }

        return $this->render('portal/portal/details.html.twig', [
            'search_page' => $search_page,
            'myPager' => $pagerfanta,
            'formAidSearch' => $formAidSearch->createView(),
            'showExtended' => $showExtended,
            'formAlertCreate' => $formAlertCreate,
            'querystring' => $queryString,
            'perimeterName' => (isset($aidParams['perimeterFrom']) && $aidParams['perimeterFrom'] instanceof Perimeter) ? $aidParams['perimeterFrom']->getName() : '',
            'categoriesName' => $categoriesName
        ]);
    }
}
