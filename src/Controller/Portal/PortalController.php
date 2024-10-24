<?php

namespace App\Controller\Portal;

use App\Controller\Aid\AidController;
use App\Controller\FrontController;
use App\Entity\Alert\Alert;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Form\Admin\Filter\DateRangeType;
use App\Form\Aid\AidSearchTypeV2;
use App\Form\Alert\AlertCreateType;
use App\Repository\Log\LogAidViewRepository;
use App\Repository\Search\SearchPageRepository;
use App\Service\Aid\AidSearchClass;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Log\LogService;
use App\Service\Matomo\MatomoService;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class PortalController extends FrontController
{
    const DATE_START_MATOMO = '2024-02-19';

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
        UserService $userService,
        ManagerRegistry $managerRegistry,
        LogService $logService,
        ReferenceService $referenceService
    ): Response {

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

        // redirection vers un autre portail
        if ($search_page->getSearchPageRedirect()) {
            return $this->redirectToRoute('app_portal_portal_details', ['slug' => $search_page->getSearchPageRedirect()->getSlug()]);
        }

        // converti la querystring en parametres
        $aidParams = [
            'showInSearch' => true,
        ];
        if (!$search_page->getOrganizationTypes()->isEmpty()) {
            $aidParams['organizationTypes'] = $search_page->getOrganizationTypes();
        }
        if (!$search_page->getCategories()->isEmpty()) {
            $aidParams['categories'] = $search_page->getCategories();
        }
        $queryString = null;
        try {
            // certaines pages ont un querystring avec https://... d'autres directement les parametres
            $query = parse_url($search_page->getSearchQuerystring())['query'] ?? null;
            $queryString = $query ?? $search_page->getSearchQuerystring();
        } catch (\Exception $e) {
            $queryString = null;
        }

        $aidSearchClass = $aidSearchFormService->getAidSearchClass(
            params: [
                'querystring' => $queryString,
                'forceOrganizationType' => null,
                'dontUseUserPerimeter' => true
            ]
        );

        if (isset($aidParams['categories']) && is_iterable($aidParams['categories'])) {
            foreach ($aidParams['categories'] as $category) {
                $aidSearchClass->addCategoryId($category);
            }
        }

        // form recherche d'aide
        $formAidSearchParams = [
            'method' => 'GET',
            'action' => '#aid-list',
            'extended' => true,
            'searchPage' => $search_page
        ];
        
        // formulaire recherche aides
        $formAidSearch = $this->createForm(
            AidSearchTypeV2::class,
            $aidSearchClass,
            $formAidSearchParams
        );

        // on force le formulaire étendu
        $showExtended = true;

        // parametres pour requetes aides
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));

        // // le paginateur
        $aidParams['searchPage'] = $search_page;
        $aids = $aidService->searchAids($aidParams);
        try {
            $adapter = new ArrayAdapter($aids);
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage(AidController::NB_AID_BY_PAGE);
            $pagerfanta->setCurrentPage($currentPage);
        } catch (OutOfRangeCurrentPageException $e) {
            $this->addFlash(
                FrontController::FLASH_ERROR,
                'Le numéro de page demandé n\'existe pas'
            );
            $newUrl = preg_replace('/(page=)[^\&]+/', 'page=' . $pagerfanta->getNbPages(), $requestStack->getCurrentRequest()->getRequestUri());
            return new RedirectResponse($newUrl);
        }

        // Log recherche
        $logParams = [
            'organizationTypes' => (isset($aidParams['organizationType'])) ? [$aidParams['organizationType']] : null,
            'querystring' => $querystring ?? null,
            'resultsCount' => $pagerfanta->getNbResults(),
            'host' => $requestStack->getCurrentRequest()->getHost(),
            'perimeter' => $aidParams['perimeterFrom'] ?? null,
            'search' => $aidParams['keyword'] ?? null,
            'organization' => ($user instanceof User && $user->getDefaultOrganization()) ? $user->getDefaultOrganization() : null,
            'backers' => $aidParams['backers'] ?? null,
            'categories' => $aidParams['categories'] ?? null,
            'programs' => $aidParams['programs'] ?? null,
            'source' => $search_page->getSlug(),
            'user' => $user ?? null
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

        // pour avoir la recherche surlignée
        $highlightedWords = $requestStack->getCurrentRequest()->getSession()->get('highlightedWords', []);

        if (isset($aidSearchClass) && $aidSearchClass instanceof AidSearchClass) {
            $highlightedWords = [];
            if ($aidSearchClass->getKeyword()) {
                // on va chercher les synonymes
                $synonyms = $referenceService->getSynonymes($aidSearchClass->getKeyword());
                if (isset($synonyms['intentions_string'])) {
                    $keywords = str_getcsv($synonyms['intentions_string'], ' ', '"');
                    foreach ($keywords as $keyword) {
                        if ($keyword && trim($keyword) !== '') {
                            $highlightedWords[] = $keyword;
                        }
                    }
                }
                if (isset($synonyms['objects_string'])) {
                    $keywords = str_getcsv($synonyms['objects_string'], ' ', '"');
                    foreach ($keywords as $keyword) {
                        if ($keyword && trim($keyword) !== '') {
                            $highlightedWords[] = $keyword;
                        }
                    }
                }
                if (isset($synonyms['simple_words_string'])) {
                    $keywords = str_getcsv($synonyms['simple_words_string'], ' ', '"');
                    foreach ($keywords as $keyword) {
                        if ($keyword && trim($keyword) !== '') {
                            $highlightedWords[] = $keyword;
                        }
                    }
                }

                // si la gestion des synonymes n'a pas fonctionné, on met directement la recherche
                if (count($highlightedWords) == 0) {
                    // on met la recherche dans les highlights
                    $keywords = explode(' ', $aidSearchClass->getKeyword());
                    foreach ($keywords as $keyword) {
                        if ($keyword && trim($keyword) !== '' && strlen($keyword) > 2) {
                            $highlightedWords[] = $keyword;
                        }
                    }
                }
            }
        }

        $requestStack->getCurrentRequest()->getSession()->set('highlightedWords', $highlightedWords);

        return $this->render('portal/portal/details.html.twig', [
            'search_page' => $search_page,
            'myPager' => $pagerfanta,
            'formAidSearch' => $formAidSearch->createView(),
            'showExtended' => $showExtended,
            'formAlertCreate' => $formAlertCreate,
            'querystring' => $queryString,
            'perimeterName' => (isset($aidParams['perimeterFrom']) && $aidParams['perimeterFrom'] instanceof Perimeter) ? $aidParams['perimeterFrom']->getName() : '',
            'categoriesName' => $categoriesName,
            'highlightedWords' => $highlightedWords,
            'showAudienceField' => $search_page->isShowAudienceField(),
            'showPerimeterField' => $search_page->isShowPerimeterField(),
            'showTextField' => $search_page->isShowTextField(),
            'showCategoriesField' => $search_page->isShowCategoriesField(),
            'showAidTypeField' => $search_page->isShowAidTypeField(),
            'showBackersField' => $search_page->isShowBackersField(),
            'showMobilizationStepField' => $search_page->isShowMobilizationStepField(),
        ]);
    }


    #[Route('/portails/{slug}/stats/', name: 'app_portal_portal_stats')]
    public function stats(
        $slug,
        SearchPageRepository $searchPageRepository,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService,
        RequestStack $requestStack
    ): Response {
        // charge le portail
        $search_page = $searchPageRepository->findOneBy(
            [
                'slug' => $slug
            ]
        );
        if (!$search_page instanceof SearchPage) {
            return $this->redirectToRoute('app_portal_portal');
        }

        // démarre session
        $session = new Session();

        // met id portal en session
        $session->set('searchPageId', $search_page->getId());

        // redirection vers un autre portail
        if ($search_page->getSearchPageRedirect()) {
            return $this->redirectToRoute('app_portal_portal_stats', ['slug' => $search_page->getSearchPageRedirect()->getSlug()]);
        }

        // converti la querystring en parametres
        $aidParams = [
            'showInSearch' => true,
        ];
        $queryString = null;
        try {
            // certaines pages ont un querystring avec https://... d'autres directement les parametres
            $query = parse_url($search_page->getSearchQuerystring())['query'] ?? null;
            $queryString = $query ?? $search_page->getSearchQuerystring();
        } catch (\Exception $e) {
            $queryString = null;
        }

        $aidSearchClass = $aidSearchFormService->getAidSearchClass(
            params: [
                'querystring' => $queryString,
                'forceOrganizationType' => null,
                'dontUseUserPerimeter' => true
            ]
        );

        // parametres pour requetes aides
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));

        // les aides
        $aidParams['searchPage'] = $search_page;
        $aids = $aidService->searchAids($aidParams);


        // tableau des ids des aides
        $aidIds = [];
        foreach ($aids as $aid) {
            $aidIds[] = $aid->getId();
        }
        // ids en session
        $session->set('aidIds', $aidIds);


        // défini la date de début pour les stats
        $dateStartMatomoto = new \DateTime(date(self::DATE_START_MATOMO));


        // dates par défaut
        $dateMin = new \DateTime('-1 month');
        $dateMax = new \DateTime(date('Y-m-d'));

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class);
        $formDateRange->handleRequest($requestStack->getCurrentRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();

                // met les dates en session
                $session->set('dateMin', $dateMin);
                $session->set('dateMax', $dateMax);
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        // rendu template
        return $this->render('portal/portal/stats.html.twig', [
            'search_page' => $search_page,
            'aids' => $aids,
            'dateStartMatomoto' => $dateStartMatomoto,
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax
        ]);
    }

    #[Route('/portails/stats/ajax-top-aids/', name: 'app_portal_portal_stats_ajax_top_aids', options: ['expose' => true])]
    public function ajaxTopAids(
        RequestStack $requestStack,
        LogAidViewRepository $logAidViewRepository
    ): Response {
        // vérification origine
        if (!$this->checkOrigin($requestStack)) {
            return new Response();
        }

        $session = new Session();
        $aidIds = $session->get('aidIds', []);
        $dateMin = $session->get('dateMin', new \DateTime('-1 month'));
        $dateMax = $session->get('dateMax', new \DateTime(date('Y-m-d')));

        if (count($aidIds) > 0) {
            // Top 10 aides consulter
            $topAidsViews = $logAidViewRepository->countTop([
                'aidIds' => $aidIds,
                'maxResults' => 10,
                'dateMin' => $dateMin,
                'dateMax' => $dateMax
            ]);
            foreach ($topAidsViews as $key => $topAidsView) {
                $topAidsViews[$key]['url'] = $this->generateUrl('app_aid_aid_details', ['slug' => $topAidsView['slug']], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }
        // rendu template
        return $this->render('portal/portal/_top_aids.html.twig', [
            'topAidsViews' => $topAidsViews ?? null,
        ]);
    }

    #[Route('/portails/stats/ajax-aids-view-by-month/', name: 'app_portal_portal_stats_ajax_aids_view_by_month', options: ['expose' => true])]
    public function ajaxAidViewsByMonth(
        RequestStack $requestStack,
        LogAidViewRepository $logAidViewRepository,
        ChartBuilderInterface $chartBuilderInterface
    ): Response {
        // vérification origine
        if (!$this->checkOrigin($requestStack)) {
            return new Response();
        }

        $session = new Session();
        $aidIds = $session->get('aidIds', []);

        if (count($aidIds) > 0) {
            // nombre de vues par mois
            $viewsByMonth = $logAidViewRepository->countByMonth([
                'aidIds' => $aidIds,
            ]);

            // graphique vues par mois
            $labels = [];
            $datas = [];
            foreach ($viewsByMonth as $viewByMonth) {
                $labels[] = $viewByMonth['monthCreate'];
                $datas[] = $viewByMonth['nb'];
            }
            $chartViewsByMonth = $chartBuilderInterface->createChart(Chart::TYPE_LINE);
            $chartViewsByMonth->setData([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Vues des aides par mois',
                        'backgroundColor' => 'rgb(255, 255, 255)',
                        'borderColor' => 'rgb(255, 0, 0)',
                        'data' => $datas,
                    ],
                ],
            ]);
        }

        // rendu template
        return $this->render('portal/portal/_aid_views_by_month.html.twig', [
            'chartViewsByMonth' => $chartViewsByMonth ?? null,
        ]);
    }

    #[Route('/portails/stats/ajax-aids-view-by-organization-type/', name: 'app_portal_portal_stats_ajax_aids_view_by_organization_type', options: ['expose' => true])]
    public function ajaxAidViewsByOrganizationType(
        RequestStack $requestStack,
        LogAidViewRepository $logAidViewRepository,
        ChartBuilderInterface $chartBuilderInterface
    ): Response {
        // vérification origine
        if (!$this->checkOrigin($requestStack)) {
            return new Response();
        }

        $session = new Session();
        $aidIds = $session->get('aidIds', []);
        $dateMin = $session->get('dateMin', new \DateTime('-1 month'));
        $dateMax = $session->get('dateMax', new \DateTime(date('Y-m-d')));

        if (count($aidIds) > 0) {
            // les types d'organization les plus demandeurs
            $organizationTypes = $logAidViewRepository->countOrganizationTypes([
                'aidIds' => $aidIds,
                'dateMin' => $dateMin,
                'dateMax' => $dateMax
            ]);

            // première boucle pour faire les pourcentages
            $total = 0;
            foreach ($organizationTypes as $organizationType) {
                $total += $organizationType['nb'];
            }
            foreach ($organizationTypes as $key => $organizationType) {
                $organizationTypes[$key]['percentage'] = $total == 0 ? 0 : number_format(($organizationType['nb'] * 100 / $total), 2);
            }

            // datas pour le graphique
            $labels = [];
            $datas = [];
            foreach ($organizationTypes as $organizationType) {
                $labels[] = $organizationType['name'] . ' (' . $organizationType['percentage'] . '%)';
                ;
                $datas[] = $organizationType['nb'];
            }
            $colors = $this->getPieColors($organizationTypes);
            $chartOrganizationTypes = $chartBuilderInterface->createChart(Chart::TYPE_PIE);
            $chartOrganizationTypes->setData([
                'labels' => $labels,
                'datasets' => [
                    [
                        'backgroundColor' => $colors,
                        'data' => $datas,
                    ],
                ],
            ]);
            $chartOrganizationTypes->setOptions([
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Nombre de type de structure unique ayant consulté les aides',
                    ],
                ],
            ]);
        }

        // rendu template
        return $this->render('portal/portal/_aid_views_by_organization_type.html.twig', [
            'chartOrganizationTypes' => $chartOrganizationTypes ?? null,
        ]);
    }

    #[Route('/portails/stats/ajax-aids-visits-by-month/', name: 'app_portal_portal_stats_ajax_visits_by_month', options: ['expose' => true])]
    public function ajaxVisitsByMonth(
        RequestStack $requestStack,
        SearchPageRepository $searchPageRepository,
        MatomoService $matomoService,
        ChartBuilderInterface $chartBuilderInterface
    ): Response {
        // vérification origine
        if (!$this->checkOrigin($requestStack)) {
            return new Response();
        }

        $session = new Session();
        $searchPageId = $session->get('searchPageId', null);

        if (!$searchPageId) {
            return new Response();
        }

        $search_page = $searchPageRepository->find($searchPageId);
        if (!$search_page instanceof SearchPage) {
            return new Response();
        }
        // // défini la date de début pour les stats
        $dateStartMatomoto = new \DateTime(date(self::DATE_START_MATOMO));
        $dateStartStats = $dateStartMatomoto > $search_page->getTimeCreate() ? $dateStartMatomoto : $search_page->getTimeCreate();

        // nombre de visites du portail par mois
        $today = new \DateTime(date('Y-m-d'));
        $url = urlencode($this->generateUrl('app_portal_portal_details', ['slug' => $search_page->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL));
        $visitsByMonth = $matomoService->getMatomoStats(
            'VisitsSummary.get',
            'pageUrl==' . $url,
            $dateStartStats->format('Y-m-d'),
            $today->format('Y-m-d'),
            'month'
        );
        $labels = [];
        $datas = [];
        foreach ($visitsByMonth as $key => $visitByMonth) {
            $labels[] = $key;
            $datas[] = isset($visitByMonth[0]) ? $visitByMonth[0]->nb_visits : 0;
        }
        $chartVisitsByMonth = $chartBuilderInterface->createChart(Chart::TYPE_LINE);
        $chartVisitsByMonth->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Visites du portail par mois',
                    'backgroundColor' => 'rgb(255, 255, 255)',
                    'borderColor' => 'rgb(255, 0, 0)',
                    'data' => $datas,
                ],
            ],
        ]);

        // rendu template
        return $this->render('portal/portal/_visits_by_month.html.twig', [
            'chartVisitsByMonth' => $chartVisitsByMonth ?? null,
        ]);
    }

    private function getPieColors(array $array): array
    {
        $colorsBySlug = [
            'commune' => 'rgb(255, 99, 132)',
            'epci' => 'rgb(54, 162, 235)',
            'department' => 'rgb(255, 205, 86)',
            'region' => 'rgb(75, 192, 192)',
            'special' => 'rgb(153, 102, 255)',
            'public-org' => 'rgb(255, 159, 64)',
            'public-cies' => 'rgb(201, 203, 207)',
            'association' => 'rgb(0, 255, 0)',
            'private-sector' => 'rgb(128, 128, 128)',
            'private-person' => 'rgb(0, 0, 255)',
            'farmer' => 'rgb(255, 255, 0)',
            'researcher' => 'rgb(255, 0, 255)',
            'other' => 'rgb(0, 255, 255)',
        ];
        $returnColors = [];

        foreach ($array as $key => $item) {
            if (!isset($colorsBySlug[$item['slug']])) {
                $returnColors[] = $colorsBySlug['other'];
            } else {
                $returnColors[] = $colorsBySlug[$item['slug']];
            }
        }

        return $returnColors;
    }

    private function checkOrigin(RequestStack $requestStack): bool
    {
        // vérification origine
        $request = $requestStack->getCurrentRequest();
        $origin = $request->headers->get('sec-fetch-site');

        return $origin == 'same-origin';
    }
}
