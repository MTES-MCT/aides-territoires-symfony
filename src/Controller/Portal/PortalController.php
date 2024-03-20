<?php

namespace App\Controller\Portal;

use App\Controller\Aid\AidController;
use App\Controller\FrontController;
use App\Entity\Alert\Alert;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Form\Aid\AidSearchTypeV2;
use App\Form\Alert\AlertCreateType;
use App\Repository\Aid\AidRepository;
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
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

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
        UserService $userService,
        ManagerRegistry $managerRegistry,
        LogService $logService,
        ReferenceService $referenceService
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

        // redirection vers un autre portail
        if ($search_page->getSearchPageRedirect()) {
            return $this->redirectToRoute('app_portal_portal_details', ['slug' => $search_page->getSearchPageRedirect()->getSlug()]);
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

            // $aidParams = array_merge($aidParams, $aidSearchFormService->convertQuerystringToParams($queryString));
            
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

         // form recherche d'aide
        $formAidSearchParams = [
            'method' => 'GET',
            'action' => '#aid-list',
            'extended' => true,
        ];

        // formulaire recherche aides
        $formAidSearch = $this->createForm(
            AidSearchTypeV2::class,
            $aidSearchClass,
            $formAidSearchParams
        );
        $formAidSearch->handleRequest($requestStack->getCurrentRequest());

        // check si on affiche ou pas le formulaire étendu
        $showExtended = $aidSearchFormService->setShowExtendedV2($aidSearchClass);

        // parametres pour requetes aides
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));

        // // le paginateur
        $aidParams['searchPage'] = $search_page;
        $aids = $aidService->searchAids($aidParams);
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
            'source' => $search_page->getSlug()
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

        if (isset($aidSearchClass) and $aidSearchClass instanceof AidSearchClass) {
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
            'highlightedWords' => $highlightedWords
        ]);
    }


    #[Route('/portails/{slug}/stats/', name: 'app_portal_portal_stats')]
    public function stats(
        $slug,
        SearchPageRepository $searchPageRepository,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService,
        UserService $userService,
        LogAidViewRepository $logAidViewRepository,
        MatomoService $matomoService,
        ChartBuilderInterface $chartBuilderInterface
    ): Response
    {
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

            // $aidParams = array_merge($aidParams, $aidSearchFormService->convertQuerystringToParams($queryString));
            
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

        // Top 10 aides consulter
        $topAidsViews = $logAidViewRepository->countTop([
            'aidIds' => $aidIds,
            'maxResults' => 10
        ]);
        foreach ($topAidsViews as $key => $topAidsView) {
            $topAidsViews[$key]['url'] = $this->generateUrl('app_aid_aid_details', ['slug' => $topAidsView['slug']], UrlGeneratorInterface::ABSOLUTE_URL);
        }

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


        // les types d'organization les plus demandeurs
        $organizationTypes = $logAidViewRepository->countOrganizationTypes([
            'aidIds' => $aidIds
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
            $labels[] = $organizationType['name']. ' ('.$organizationType['percentage'].'%)';;
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

        // défini la date de début pour les stats
        $dateStartMatomoto = new \DateTime(date('2024-02-19'));
        $dateStartStats = $dateStartMatomoto > $search_page->getTimeCreate() ? $dateStartMatomoto : $search_page->getTimeCreate();

        // nombre de visites du portail par mois
        $today = new \DateTime(date('Y-m-d'));
        $url = urlencode($this->generateUrl('app_portal_portal_details', ['slug' => $slug], UrlGeneratorInterface::ABSOLUTE_URL));
        $url = urlencode('https://aides-territoires.beta.gouv.fr/portails/francemobilites/');
        $visitsByMonth = $matomoService->getMatomoStats(
            'VisitsSummary.get',
            'pageUrl=='.$url,
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
        return $this->render('portal/portal/stats.html.twig', [
            'search_page' => $search_page,
            'aids' => $aids,
            'topAidsViews' => $topAidsViews,
            'viewsByMonth' => $viewsByMonth,
            'chartViewsByMonth' => $chartViewsByMonth,
            'organizationTypes' => $organizationTypes,
            'chartOrganizationTypes' => $chartOrganizationTypes,
            'chartVisitsByMonth' => $chartVisitsByMonth,
            'dateStartMatomoto' => $dateStartMatomoto
        ]);
    }


    private function getPieColors(array $array) {
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
}
