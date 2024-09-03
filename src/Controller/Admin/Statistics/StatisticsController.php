<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidProject;
use App\Entity\Alert\Alert;
use App\Entity\Backer\Backer;
use App\Entity\Log\LogAidApplicationUrlClick;
use App\Entity\Log\LogAidContactClick;
use App\Entity\Log\LogAidOriginUrlClick;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogAidView;
use App\Entity\Log\LogUserLogin;
use App\Entity\Organization\Organization;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Form\Admin\Filter\DateRangeType;
use App\Service\Matomo\MatomoService;
use App\Service\User\UserService;
use App\Service\Various\Breadcrumb;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends DashboardController
{
    const NB_USER_BY_PAGE = 50;
    const NB_ORGANIZATION_BY_PAGE = 50;
    const NB_PROJECT_BY_PAGE = 50;

    public function __construct(
        protected UserService $userService,
        protected ManagerRegistry $managerRegistry,
        protected AdminUrlGenerator $adminUrlGenerator,
        protected ChartBuilderInterface $chartBuilderInterface,
        protected Breadcrumb $breadcrumb
    ) {
    }

    private function getChartObjectif(int $nbCurrent, int $nbTotal, ?int $nbObjectif): Chart
    {
        $total = $nbTotal - $nbCurrent;
        if ($total < 0) {
            $total = 0;
        }

        $chart = $this->chartBuilderInterface->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => ['Epci'],
            'datasets' => [
                [
                    'label' => 'Actuel',
                    'backgroundColor' => 'rgb(75, 192, 192)',
                    'data' => [$nbCurrent],
                ],
                [
                    'label' => 'Total',
                    'backgroundColor' => '#ccc',
                    'data' => [$total],
                ],
            ],
        ]);

        $options = [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => true
                ],
                'x' => [
                    'stacked' => true
                ]
            ]
        ];
        if ($nbObjectif) {
            $options['plugins'] = [
                'annotation' => [
                    'annotations' => [
                        'line1' => [
                            'type' => 'line',
                            'yMin' => $nbObjectif,
                            'yMax' => $nbObjectif,
                            'borderColor' => 'rgb(255, 99, 132)',
                            'borderWidth' => 4,
                            'clip' => false, // add this line
                            'label' => [
                                'enabled' => true,
                                'content' => 'Objectif ' . $nbObjectif,
                                'position' => 'center',
                                'display' => true
                            ],
                        ],
                    ],
                ],
            ];
        }

        $chart->setOptions($options);


        return $chart;
    }

    private function getStatsGlobal(): array
    {
        $stats = [
            'nbUsers' => $this->managerRegistry->getRepository(User::class)->count(['isBeneficiary' => true]),
            'nbOrganizations' => $this->managerRegistry->getRepository(Organization::class)->count(['isImported' => false]),
            'nbInterco' => $this->managerRegistry->getRepository(Organization::class)->countInterco([]),
            'nbProjects' => $this->managerRegistry->getRepository(Project::class)->count([]),
            'nbAidProjects' => $this->managerRegistry->getRepository(AidProject::class)->countDistinctAids([]),
            'nbAidsLive' => $this->managerRegistry->getRepository(Aid::class)->countLives([]),
            'nbBackers' => $this->managerRegistry->getRepository(Backer::class)->countWithAids([]),
            'nbSearchPages' => $this->managerRegistry->getRepository(SearchPage::class)->count([]),
            'nbCommune' => $this->managerRegistry->getRepository(Organization::class)->countCommune([]),
            'nbCommuneTotal' => 35039,
            'nbCommuneObjectif' => 15000,
            'nbEpci' => $this->managerRegistry->getRepository(Organization::class)->countEpci([]),
            'nbEpciTotal' => 1256,
            'nbEpciObjectif' => (int) 1256 * 0.75,
            'nbEpciObjectifPercentage' => round((1256 * 0.75 / 1256) * 100, 1),

        ];
        $stats['nbEpciPercentage'] = round(($stats['nbEpci'] / $stats['nbEpciTotal']) * 100, 1);
        return $stats;
    }

    #[Route('/admin/statistics/dashboard', name: 'admin_statistics_dashboard')]
    public function dashboard(
        AdminContext $adminContext,
        MatomoService $matomoService
    ): Response {
        $statsGlobal = $this->getStatsGlobal();
        $chartCommune = $this->getChartObjectif($statsGlobal['nbCommune'], $statsGlobal['nbCommuneTotal'], $statsGlobal['nbCommuneObjectif']);
        $chartEpci = $this->getChartObjectif($statsGlobal['nbEpci'], $statsGlobal['nbEpciTotal'], $statsGlobal['nbEpciObjectif']);

        // dates par défaut
        $dateMin = new \DateTime('-1 month');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class);
        $formDateRange->handleRequest($adminContext->getRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        // stats matomo
        $statsMatomo = $matomoService->getMatomoStats(
            apiMethod: 'VisitsSummary.get',
            customSegment: null,
            fromDateString: $dateMin->format('Y-m-d'),
            toDateString: $dateMax->format('Y-m-d')
        );
        $statsMatomoActions = $matomoService->getMatomoStats(
            apiMethod: 'Actions.get',
            customSegment: null,
            fromDateString: $dateMin->format('Y-m-d'),
            toDateString: $dateMax->format('Y-m-d')
        );
        $statsMatomoLast10Weeks = $matomoService->getMatomoStats(
            apiMethod: 'VisitsSummary.get',
            period: 'week',
            fromDateString: 'last10',
            toDateString: null
        );

        // nombre d'aides vues
        $nbAidViews = $this->managerRegistry->getRepository(LogAidView::class)->countAidsViews([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'excludeSources' => ['api'],
        ]);

        $nbAidViewsDistinct = $this->managerRegistry->getRepository(LogAidView::class)->countAidsViews([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'excludeSources' => ['api'],
            'distinctAids' => true
        ]);

        $labels = [];
        $visitsUnique = [];
        $registers = [];
        $alerts = [];

        foreach ($statsMatomoLast10Weeks as $key => $stats) {
            $dates = explode(',', $key);
            $dateStart = new \DateTime($dates[0]);
            $dateEnd = new \DateTime($dates[1]);
            // dd($registersByWeek, $dateStart->format('Y-W'));
            $labels[] = $dateStart->format('d/m/Y') . ' au ' . $dateEnd->format('d/m/Y');
            $visitsUnique[] = $stats[0]->nb_uniq_visitors ?? 0;
            $registers[] = $this->managerRegistry->getRepository(User::class)->countRegisters(['dateCreateMin' => $dateStart, 'dateCreateMax' => $dateMax]);
            $alerts[] = $this->managerRegistry->getRepository(Alert::class)->countCustom(['dateCreateMin' => $dateStart, 'dateCreateMax' => $dateMax]);
        }
        $chartLast10Weeks = $this->chartBuilderInterface->createChart(Chart::TYPE_LINE);
        $chartLast10Weeks->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Visites uniques hebdomadaires',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'data' => $visitsUnique,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(255, 99, 132)', // couleur de la ligne
                ],
                [
                    'label' => 'Inscriptions',
                    'backgroundColor' => 'rgb(75, 192, 192)',
                    'data' => $registers,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(75, 192, 192)', // couleur de la ligne
                ],
                [
                    'label' => 'Alertes créées',
                    'backgroundColor' => 'rgb(54, 162, 235)',
                    'data' => $alerts,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(54, 162, 235)', // couleur de la ligne
                ],
            ],
        ]);


        return $this->render('admin/statistics/dashboard_consultation.html.twig', [
            // globale
            'statsGlobal' => $statsGlobal,
            'chartCommune' => $chartCommune,
            'chartEpci' => $chartEpci,

            // specific
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'statsMatomo' => $statsMatomo[0] ?? [],
            'statsMatomoActions' => $statsMatomoActions[0] ?? [],
            'statsMatomoLast10Weeks' => $statsMatomoLast10Weeks,
            'nbAidViews' => $nbAidViews,
            'nbAidViewsDistinct' => $nbAidViewsDistinct,
            'chartLast10Weeks' => $chartLast10Weeks,

            'consultationSelected' => true,
        ]);
    }

    #[Route('/admin/statistics/acquisition/', name: 'admin_statistics_acquisition')]
    public function acquisition(
        AdminContext $adminContext,
        MatomoService $matomoService
    ): Response {
        $statsGlobal = $this->getStatsGlobal();
        $chartCommune = $this->getChartObjectif($statsGlobal['nbCommune'], $statsGlobal['nbCommuneTotal'], $statsGlobal['nbCommuneObjectif']);
        $chartEpci = $this->getChartObjectif($statsGlobal['nbEpci'], $statsGlobal['nbEpciTotal'], $statsGlobal['nbEpciObjectif']);

        // dates par défaut
        $dateMin = new \DateTime('-1 month');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class);
        $formDateRange->handleRequest($adminContext->getRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }


        // stats matomo
        $statsMatomoReferer = $matomoService->getMatomoStats(
            apiMethod: 'Referrers.get',
            customSegment: null,
            fromDateString: $dateMin->format('Y-m-d'),
            toDateString: $dateMax->format('Y-m-d')
        );
        $statsMatomoRefererAll = $matomoService->getMatomoStats(
            apiMethod: 'Referrers.getAll',
            customSegment: null,
            fromDateString: $dateMin->format('Y-m-d'),
            toDateString: $dateMax->format('Y-m-d')
        );
        $tmp_referrers = [];
        $nb_referrers_total = 0;
        $nb_referrers_total_without_search = 0;
        foreach ($statsMatomoRefererAll as $referrer) {
            $nb_visits = $referrer->nb_visits;
            $is_search = $referrer->label == "Keyword not defined";
            if ($is_search) {
                $label = "Recherche";
                $nb_referrers_total += $nb_visits;
            } else {
                $label = $referrer->label;
                $nb_referrers_total += $nb_visits;
                $nb_referrers_total_without_search += $nb_visits;
            }
            $tmp_referrers[$label] = $nb_visits;
        }

        $referrers = [];
        foreach ($tmp_referrers as $label => $nb_visits) {
            if ($label != "Recherche") {
                $referrers[$label] = [
                    'label' => $label,
                    'nb_visits' => $nb_visits,
                    'percentage_total' => round($nb_visits / $nb_referrers_total * 100, 1),
                    'percentage_without_search' => round($nb_visits / $nb_referrers_total_without_search * 100, 1)
                ];
            } else {
                $referrers[$label] = [
                    'label' => $label,
                    'nb_visits' => $nb_visits,
                    'percentage_total' => round($nb_visits / $nb_referrers_total * 100, 1),
                    'percentage_without_search' => '-'
                ];
            }
        }
        $referrers = array_slice($referrers, 0, 10, true);

        $userRegisters = $this->managerRegistry->getRepository(User::class)->findCustom([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'orderBy' => [
                'sort' => 'u.dateCreate',
                'order' => 'DESC'
            ]
        ]);

        $userRegistersByDate = [];
        foreach ($userRegisters as $userRegister) {
            $date = $userRegister->getDateCreate()->format('Y-m-d');
            if (!isset($userRegistersByDate[$date])) {
                $userRegistersByDate[$date] = 0;
            }
            $userRegistersByDate[$date]++;
        }
        $userRegisters = array_slice($userRegisters, 0, 10, true);

        $labels = [];
        $datas = [];
        foreach ($userRegistersByDate as $date => $nbRegister) {
            $labels[] = $date;
            $datas[] = $nbRegister;
        }

        $chartInscriptions = $this->chartBuilderInterface->createChart(Chart::TYPE_LINE);
        $chartInscriptions->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Inscrites',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'data' => $datas,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(255, 99, 132)', // couleur de la ligne
                    'tension' => 0.4, // ajoute une courbure aux lignes
                ],
            ],
        ]);

        return $this->render('admin/statistics/dashboard_acquisition.html.twig', [
            // globale
            'statsGlobal' => $statsGlobal,
            'chartCommune' => $chartCommune,
            'chartEpci' => $chartEpci,
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,

            'statsMatomoReferer' => $statsMatomoReferer[0] ?? [],
            'referrers' => $referrers,
            'userRegisters' => $userRegisters,
            'acquisitionSelected' => true,
            'chartInscriptions' => $chartInscriptions
        ]);
    }

    #[Route('/admin/statistics/engagement/', name: 'admin_statistics_engagement')]
    public function engagement(
        AdminContext $adminContext,
        MatomoService $matomoService
    ): Response {
        $statsGlobal = $this->getStatsGlobal();
        $chartCommune = $this->getChartObjectif($statsGlobal['nbCommune'], $statsGlobal['nbCommuneTotal'], $statsGlobal['nbCommuneObjectif']);
        $chartEpci = $this->getChartObjectif($statsGlobal['nbEpci'], $statsGlobal['nbEpciTotal'], $statsGlobal['nbEpciObjectif']);

        // dates par défaut
        $dateMin = new \DateTime('-1 month');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class);
        $formDateRange->handleRequest($adminContext->getRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        // nb connexions
        $nbLogins = $this->managerRegistry->getRepository(LogUserLogin::class)->countCustom([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'distinctUser' => true
        ]);
        $nbAidSearch = $this->managerRegistry->getRepository(LogAidSearch::class)->countCustom([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbAlerts = $this->managerRegistry->getRepository(Alert::class)->countCustom([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbContactClicks = $this->managerRegistry->getRepository(LogAidContactClick::class)->countCustom([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);
        $nbInformations = $this->managerRegistry->getRepository(LogAidOriginUrlClick::class)->countCustom([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);
        $nbApplications = $this->managerRegistry->getRepository(LogAidApplicationUrlClick::class)->countCustom([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        $statsMatomoLast10Weeks = $matomoService->getMatomoStats(
            apiMethod: 'VisitsSummary.get',
            period: 'week',
            fromDateString: 'last10',
            toDateString: null
        );

        $labels = [];
        $registers = [];
        $registersWithAid = [];
        $registersWithProject = [];
        foreach ($statsMatomoLast10Weeks as $key => $stats) {
            $dates = explode(',', $key);
            $dateStart = new \DateTime($dates[0]);
            $dateEnd = new \DateTime($dates[1]);

            $labels[] = $dateStart->format('d/m/Y') . ' au ' . $dateEnd->format('d/m/Y');
            $registers[] = $this->managerRegistry->getRepository(User::class)->countRegisters([
                'dateCreateMin' => $dateStart,
                'dateCreateMax' => $dateMax,

            ]);
            $registersWithAid[] = $this->managerRegistry->getRepository(User::class)->countRegistersWithAid([
                'dateCreateMin' => $dateStart,
                'dateCreateMax' => $dateMax,

            ]);
            $registersWithProject[] = $this->managerRegistry->getRepository(User::class)->countRegistersWithProject([
                'dateCreateMin' => $dateStart,
                'dateCreateMax' => $dateMax,
            ]);
            ;
        }

        $chartRegisters = $this->chartBuilderInterface->createChart(Chart::TYPE_BAR);
        $chartRegisters->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Inscriptions',
                    'backgroundColor' => 'rgb(255, 0, 0)',
                    'data' => $registers,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(255, 0, 0)', // couleur de la ligne
                ],
                [
                    'label' => 'Avec aides',
                    'backgroundColor' => 'rgb(0, 255, 0)',
                    'data' => $registersWithAid,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(0, 255, 0)', // couleur de la ligne
                ],
                [
                    'label' => 'Avec projets',
                    'backgroundColor' => 'rgb(0, 0, 255)',
                    'data' => $registersWithProject,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(0, 0, 255)', // couleur de la ligne
                ],
            ],
        ]);

        // top aides
        $statsMatomoTopAids = $matomoService->getMatomoStats(
            apiMethod: 'Actions.getPageUrls',
            fromDateString: $dateMin->format('Y-m-d'),
            toDateString: $dateMax->format('Y-m-d'),
            options: [
                'flat' => 1,
                'filter_column' => 'label',
                'filter_limit' => 100 * 1.3,
                'filter_pattern' => '^/(aides/)?([a-z0-9]){4}-'
            ]
        );
        // dd($statsMatomoTopAids);
        $topAids = [];
        foreach ($statsMatomoTopAids as $stats) {
            preg_match('/^\/(aides\/)?(.*)\/$/', $stats->label, $matches);
            $slug = $matches[2] ?? null;
            $aid = null;
            if ($slug) {
                $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(['slug' => $slug]);
            }

            $nbContactClicks = $nbInformations = $nbApplications = 0;
            $allClicks = 0; // aid.contact_clicks_count + aid.origin_clicks_count + aid.application_clicks_count
            if ($aid instanceof Aid) {
                $nbContactClicks = $this->managerRegistry->getRepository(LogAidContactClick::class)->countCustom([
                    'dateMin' => $dateMin,
                    'dateMax' => $dateMax,
                    'aid' => $aid
                ]);
                $nbInformations = $this->managerRegistry->getRepository(LogAidOriginUrlClick::class)->countCustom([
                    'dateMin' => $dateMin,
                    'dateMax' => $dateMax,
                    'aid' => $aid
                ]);
                $nbApplications = $this->managerRegistry->getRepository(LogAidApplicationUrlClick::class)->countCustom([
                    'dateMin' => $dateMin,
                    'dateMax' => $dateMax,
                    'aid' => $aid
                ]);
                $allClicks = $nbContactClicks + $nbInformations + $nbApplications;
            }

            $nbUniqVisitors = $stats->nb_uniq_visitors ?? $stats->sum_daily_nb_uniq_visitors ?? 0;
            $conversionvalue = $nbUniqVisitors == 0 ? 0 : 100 * $allClicks / $nbUniqVisitors;
            $topAids[] = [
                'aid' => $aid,
                'nbVisits' => $stats->nb_visits ?? 0,
                'nbUniqVisitors' => $nbUniqVisitors,
                'nbClicks' => $allClicks,
                'conversionRate' => $conversionvalue,
                'nbInformations' => $nbInformations,
                'nbApplications' => $nbApplications,
            ];
        }

        $dateStart = new \DateTime('last month');
        $dateEnd = clone $dateStart;
        $dateEnd->sub(new \DateInterval('P5M'));

        $currentDate = clone $dateStart;
        $labels = [];
        $usersConnected = [];
        $communesConnected = [];
        $epcisConnected = [];
        while ($currentDate > $dateEnd) {
            // user connectés
            $nbUsersConnected = $this->managerRegistry->getRepository(LogUserLogin::class)->countCustom([
                'month' => $currentDate,
                'distinctUser' => true,
                'excludeAdmins' => true
            ]);
            $nbCommunesConnected = $this->managerRegistry->getRepository(LogUserLogin::class)->countCustom([
                'month' => $currentDate,
                'distinctUser' => true,
                'isCommune' => true,
                'excludeAdmins' => true
            ]);
            $nbEpcisConnected = $this->managerRegistry->getRepository(LogUserLogin::class)->countCustom([
                'month' => $currentDate,
                'distinctUser' => true,
                'isEpci' => true,
                'excludeAdmins' => true
            ]);

            $labels[] = $currentDate->format('m-Y');
            $usersConnected[] = $nbUsersConnected;
            $communesConnected[] = $nbCommunesConnected;
            $epcisConnected[] = $nbEpcisConnected;

            $currentDate->sub(new \DateInterval('P1M'));
        }


        $chartActivity = $this->chartBuilderInterface->createChart(Chart::TYPE_LINE);
        $chartActivity->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Tous les comptes',
                    'backgroundColor' => 'rgb(255, 0, 0)',
                    'data' => $usersConnected,
                    // 'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(255, 0, 0)', // couleur de la ligne
                    'tension' => 0.4, // ajoute une courbure aux lignes
                ],
                [
                    'label' => 'Les comptes des communes',
                    'backgroundColor' => 'rgb(0, 255, 0)',
                    'data' => $communesConnected,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(0, 255, 0)', // couleur de la ligne
                    'tension' => 0.4, // ajoute une courbure aux lignes
                ],
                [
                    'label' => 'Les comptes des EPCI',
                    'backgroundColor' => 'rgb(0, 0, 255)',
                    'data' => $epcisConnected,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(0, 0, 255)', // couleur de la ligne
                    'tension' => 0.4, // ajoute une courbure aux lignes
                ],
            ],
        ]);

        return $this->render('admin/statistics/dashboard_engagement.html.twig', [
            // globale
            'statsGlobal' => $statsGlobal,
            'chartCommune' => $chartCommune,
            'chartEpci' => $chartEpci,
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,

            'nbLogins' => $nbLogins,
            'nbAidSearch' => $nbAidSearch,
            'nbAlerts' => $nbAlerts,
            'nbContactClicks' => $nbContactClicks,
            'nbInformations' => $nbInformations,
            'nbApplications' => $nbApplications,
            'chartRegisters' => $chartRegisters,
            'topAids' => $topAids,
            'chartActivity' => $chartActivity,

            'engagementSelected' => true
        ]);
    }

    #[Route('/admin/statistics/porteurs/', name: 'admin_statistics_porteurs')]
    public function porteurs(
        AdminContext $adminContext
    ): Response {
        $statsGlobal = $this->getStatsGlobal();
        $chartCommune = $this->getChartObjectif($statsGlobal['nbCommune'], $statsGlobal['nbCommuneTotal'], $statsGlobal['nbCommuneObjectif']);
        $chartEpci = $this->getChartObjectif($statsGlobal['nbEpci'], $statsGlobal['nbEpciTotal'], $statsGlobal['nbEpciObjectif']);

        // dates par défaut
        $dateMin = new \DateTime('-1 month');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class);
        $formDateRange->handleRequest($adminContext->getRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        $nbBeneficiaries = $this->managerRegistry->getRepository(User::class)->countBeneficiaries([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbOrganizationWithBeneficiary = $this->managerRegistry->getRepository(Organization::class)->countWithUserBeneficiary([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'isImported' => false,
        ]);
        $nbProjects = $this->managerRegistry->getRepository(Project::class)->countCustom([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbAidProjects = $this->managerRegistry->getRepository(AidProject::class)->countCustom([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        $nbContributors = $this->managerRegistry->getRepository(User::class)->countContributors([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbOrganizationWithContributor = $this->managerRegistry->getRepository(Organization::class)->countWithUserContributor([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'isImported' => false,
        ]);
        $nbAidsLive = $this->managerRegistry->getRepository(Aid::class)->countLives([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbBeneficiariesAndContributors = $this->managerRegistry->getRepository(User::class)->countCustom([
            'isBeneficary' => true,
            'isContributor' => true,
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);

        $dateStart = new \DateTime('-10 weeks');
        $dateEnd = new \DateTime();
        $currentDate = clone $dateStart;

        $labels = [];
        $communes = [];
        $communesWithAid = [];
        $communesWithProject = [];
        $epcis = [];
        $epcisWithAid = [];
        $epcisWithProject = [];

        while ($currentDate < $dateEnd) {
            $startOfWeek = (clone $currentDate)->setISODate($currentDate->format('Y'), $currentDate->format('W'), 1);
            $endOfWeek = (clone $currentDate)->setISODate($currentDate->format('Y'), $currentDate->format('W'), 7);

            // inscriptions communes
            $nbCommunes = $this->managerRegistry->getRepository(User::class)->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsCommune' => true,
            ]);

            // inscription communes avec une aide publiée
            $nbCommunesWithAid = $this->managerRegistry->getRepository(User::class)->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsCommune' => true,
                'organizationHasAid' => true,
            ]);

            // inscription communes avec un projet
            $nbCommunesWithProject = $this->managerRegistry->getRepository(User::class)->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsCommune' => true,
                'organizationHasProject' => true,
            ]);

            // inscriptions epci
            $nbEpcis = $this->managerRegistry->getRepository(User::class)->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsEpci' => true,
            ]);

            // inscription epci avec une aide publiée
            $nbEpcisWithAid = $this->managerRegistry->getRepository(User::class)->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsEpci' => true,
                'organizationHasAid' => true,
            ]);

            // inscription epci avec un projet
            $nbEpcisWithProject = $this->managerRegistry->getRepository(User::class)->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsEpci' => true,
                'organizationHasProject' => true,
            ]);


            $labels[] = $startOfWeek->format('d/m/Y') . ' au ' . $endOfWeek->format('d/m/Y');
            $communes[] = $nbCommunes;
            $communesWithAid[] = $nbCommunesWithAid;
            $communesWithProject[] = $nbCommunesWithProject;
            $epcis[] = $nbEpcis;
            $epcisWithAid[] = $nbEpcisWithAid;
            $epcisWithProject[] = $nbEpcisWithProject;

            $currentDate->modify('+1 week');
        }

        $chartCommunes = $this->chartBuilderInterface->createChart(Chart::TYPE_BAR);
        $chartCommunes->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Toutes les communes',
                    'backgroundColor' => 'rgb(255, 0, 0)',
                    'data' => $communes,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(255, 0, 0)', // couleur de la ligne
                ],
                [
                    'label' => 'Avec aides',
                    'backgroundColor' => 'rgb(0, 255, 0)',
                    'data' => $communesWithAid,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(0, 255, 0)', // couleur de la ligne
                ],
                [
                    'label' => 'Avec projets',
                    'backgroundColor' => 'rgb(0, 0, 255)',
                    'data' => $communesWithProject,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(0, 0, 255)', // couleur de la ligne
                ],
            ],
        ]);

        $chartEpcis = $this->chartBuilderInterface->createChart(Chart::TYPE_BAR);
        $chartEpcis->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Tous les EPCI',
                    'backgroundColor' => 'rgb(255, 0, 0)',
                    'data' => $epcis,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(255, 0, 0)', // couleur de la ligne
                ],
                [
                    'label' => 'Avec aides',
                    'backgroundColor' => 'rgb(0, 255, 0)',
                    'data' => $epcisWithAid,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(0, 255, 0)', // couleur de la ligne
                ],
                [
                    'label' => 'Avec projets',
                    'backgroundColor' => 'rgb(0, 0, 255)',
                    'data' => $epcisWithProject,
                    'fill' => false, // pour avoir une ligne sans remplissage
                    'borderColor' => 'rgb(0, 0, 255)', // couleur de la ligne
                ],
            ],
        ]);

        return $this->render('admin/statistics/dashboard_porteurs.html.twig', [
            // globale
            'statsGlobal' => $statsGlobal,
            'chartCommune' => $chartCommune,
            'chartEpci' => $chartEpci,
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,

            'nbBeneficiaries' => $nbBeneficiaries,
            'nbOrganizationWithBeneficiary' => $nbOrganizationWithBeneficiary,
            'nbProjects' => $nbProjects,
            'nbAidProjects' => $nbAidProjects,
            'nbContributors' => $nbContributors,
            'nbOrganizationWithContributor' => $nbOrganizationWithContributor,
            'nbAidsLive' => $nbAidsLive,
            'nbBeneficiariesAndContributors' => $nbBeneficiariesAndContributors,
            'chartCommunes' => $chartCommunes,
            'chartEpcis' => $chartEpcis,

            'porteursSelected' => true
        ]);
    }

    #[Route('/admin/statistics/stats-utilisateurs/', name: 'admin_statistics_users')]
    public function statsUsers(
        AdminContext $adminContext
    ): Response {
        $nbUsers = $this->managerRegistry->getRepository(User::class)->countCustom();
        $nbBeneficiaries = $this->managerRegistry->getRepository(User::class)->countBeneficiaries();
        $nbContributors = $this->managerRegistry->getRepository(User::class)->countContributors();
        $nbBeneficiariesAndContributors = $this->managerRegistry->getRepository(User::class)->countCustom([
            'isBeneficary' => true,
            'isContributor' => true,
        ]);
        $nbBeneficiariesPercent = $nbUsers == 0 ? 0 : round($nbBeneficiaries / $nbUsers * 100, 2);
        $nbContributorsPercent = $nbUsers == 0 ? 0 : round($nbContributors / $nbUsers * 100, 2);
        $nbBeneficiariesAndContributorsPercent = $nbUsers == 0 ? 0 : round($nbBeneficiariesAndContributors / $nbUsers * 100, 2);

        // dates par défaut
        $dateMin = new \DateTime('-1 month');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class, null, ['method' => 'GET']);
        $formDateRange->handleRequest($adminContext->getRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        // gestion pagination
        $currentPage = (int) $adminContext->getRequest()->get('page', 1);

        // le paginateur
        $users = $this->managerRegistry->getRepository(User::class)->getQueryBuilder([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'orderBy' => [
                'sort' => 'u.dateCreate',
                'order' => 'DESC'
            ]
        ]);
        $adapter = new QueryAdapter($users);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_USER_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        // fil arianne
        $this->breadcrumb->add(
            'Dashboard',
            $this->generateUrl('admin_statistics_dashboard')
        );
        $this->breadcrumb->add(
            'Statistiques Comptes Utilisateurs'
        );

        // rendu template
        return $this->render('admin/statistics/users.html.twig', [
            'nbUsers' => $nbUsers,
            'nbBeneficiaries' => $nbBeneficiaries,
            'nbContributors' => $nbContributors,
            'nbBeneficiariesAndContributors' => $nbBeneficiariesAndContributors,
            'nbBeneficiariesPercent' => $nbBeneficiariesPercent,
            'nbContributorsPercent' => $nbContributorsPercent,
            'nbBeneficiariesAndContributorsPercent' => $nbBeneficiariesAndContributorsPercent,
            'formDateRange' => $formDateRange,
            'myPager' => $pagerfanta,
        ]);
    }

    #[Route('/admin/statistics/stats-structures/', name: 'admin_statistics_organizations')]
    public function statsOrganizations(
        AdminContext $adminContext
    ): Response {
        $nbOrganizations = $this->managerRegistry->getRepository(Organization::class)->countCustom([
            'isImported' => false,
            'hasPerimeter' => true
        ]);
        $nbOrganizationsByType = $this->managerRegistry->getRepository(Organization::class)->countByType([
            'isImported' => false,
            'hasPerimeter' => true
        ]);
        foreach ($nbOrganizationsByType as $key => $organizationType) {
            $nbOrganizationsByType[$key]['percent'] = $nbOrganizations == 0 ? 0 : round($organizationType['nb'] / $nbOrganizations * 100, 2);
        }

        // dates par défaut
        $dateMin = new \DateTime('-1 month');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class, null, ['method' => 'GET']);
        $formDateRange->handleRequest($adminContext->getRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        // gestion pagination
        $currentPage = (int) $adminContext->getRequest()->get('page', 1);

        // le paginateur
        $organizations = $this->managerRegistry->getRepository(Organization::class)->getQueryBuilder([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'orderBy' => [
                'sort' => 'o.dateCreate',
                'order' => 'DESC'
            ]
        ]);
        $adapter = new QueryAdapter($organizations);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_ORGANIZATION_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        // fil arianne
        $this->breadcrumb->add(
            'Dashboard',
            $this->generateUrl('admin_statistics_dashboard')
        );
        $this->breadcrumb->add(
            'Statistiques Structures'
        );

        // rendu template
        return $this->render('admin/statistics/organizations.html.twig', [
            'nbOrganizations' => $nbOrganizations,
            'nbOrganizationsByType' => $nbOrganizationsByType,
            'formDateRange' => $formDateRange,
            'myPager' => $pagerfanta,
        ]);
    }

    #[Route('/admin/statistics/stats-cartographie/', name: 'admin_statistics_carto')]
    public function statsCarto(
        AdminContext $adminContext,
        KernelInterface $kernelInterface
    ): Response {
        // fichier geojson des régions pour la carte
        $regionsGeojson = file_get_contents($kernelInterface->getProjectDir() . '/datas/geojson/regions-1000m.geojson');

        // on recupère toutes les régions
        $regions = $this->managerRegistry->getRepository(Perimeter::class)->findCustom([
            'scale' => Perimeter::SCALE_REGION,
            'isObsolete' => false
        ]);

        $regionsOrgCounts = [];
        $regionsOrgCommunesMax = 0;

        // on parcours les régions
        /** @var Perimeter $region */
        foreach ($regions as $region) {
            if (!$region->getCode()) {
                continue;
            }

            // on va recupérer les organisations communes de cette région
            $nbCommune = $this->managerRegistry->getRepository(Organization::class)->countCommune([
                'perimeterRegion' => $region
            ]);

            // on regarde si on change le nombre max de communes pour une région
            $regionsOrgCommunesMax = $nbCommune > $regionsOrgCommunesMax ? $nbCommune : $regionsOrgCommunesMax;

            // on va recupérer les organisations epci de cette région
            $nbEpci = $this->managerRegistry->getRepository(Organization::class)->countEpci([
                'perimeterRegion' => $region
            ]);

            // on alimente le tableau
            $regionsOrgCounts[$region->getCode()] = [
                'name' => $region->getName(),
                'communes_count' => $nbCommune,
                'epcis_count' => $nbEpci
            ];
        }
        // libere memoire
        unset($regions);


        # Les départements
        $counties = $this->managerRegistry->getRepository(Perimeter::class)->findCustom([
            'scale' => Perimeter::SCALE_COUNTY,
            'isObsolete' => false
        ]);

        $countiesOrgCounts = [];
        $countiesOrgCommunesMax = 0;
        $countiesCode = [];
        // on parcours les départements
        /** @var Perimeter $region */
        foreach ($counties as $county) {
            if (!$county->getCode()) {
                continue;
            }

            // alimente le tableau des codes
            $countiesCode[] = $county->getCode();

            // on va recupérer les organisations communes de cette région
            $nbCommune = $this->managerRegistry->getRepository(Organization::class)->countCommune([
                'perimeterDepartment' => $county
            ]);

            // on regarde si on change le nombre max de communes pour une région
            $countiesOrgCommunesMax = $nbCommune > $countiesOrgCommunesMax ? $nbCommune : $countiesOrgCommunesMax;

            // on va recupérer les organisations epci de cette région
            $nbEpci = $this->managerRegistry->getRepository(Organization::class)->countEpci([
                'perimeterDepartment' => $county
            ]);

            // calcul le pourcentage de communes inscrites
            $nbCommuneTotalOffical = $this->getNbCommuneByDepartmentOffical($county->getCode());
            $percentCommunes = $nbCommuneTotalOffical == 0 ? 0 : round($nbCommune / $nbCommuneTotalOffical * 100, 2);

            // on alimente le tableau
            $countiesOrgCounts[$county->getCode()] = [
                'name' => $county->getName(),
                'communes_count' => $nbCommune,
                'percentage_communes' => $percentCommunes,
                'epcis_count' => $nbEpci
            ];
        }
        $countiesOrgCommunesMax = 30; // le chiffre est forcé pour l'affichage
        // libere memoire
        unset($counties);

        // recuperes toutes les organizations de type communes
        $organizationCommunes = $this->managerRegistry->getRepository(Organization::class)->findCommunes([]);

        $communes_with_org = [];
        /** @var Organization $organization */
        foreach ($organizationCommunes as $organization) {
            $key = $organization['perimeter__code'] . '-' . $organization['perimeter__name'];
            $content = [
                'organization_name' => $organization['name'],
                'user_email' => $organization['user__email'],
                'projects_count' => $organization['projects_count'],
                'date_created' => $organization['dateCreate']->format('Y-m-d'),
                'age' => $this->getAge($organization['dateCreate']),
            ];
            if (array_key_exists($key, $communes_with_org)) {
                $already_exists = false;
                foreach ($communes_with_org[$key] as &$commune) {
                    if ($commune['organization_name'] == $organization['name']) {
                        $already_exists = true;
                        $finalEmail = $commune['user_email'] ?? '';
                        $finalEmail .= $organization['user__email'] ? ', ' . $organization['user__email'] : '';
                        $commune['user_email'] = $finalEmail;
                    }
                }
                if (!$already_exists) {
                    $communes_with_org[$key][] = $content;
                }
            } else {
                $communes_with_org[$key][] = $content;
            }
        }
        // libère mémoire
        unset($organizationCommunes);

        // recuperes toutes les organizations de type Epci
        $organizations_epcis = $this->managerRegistry->getRepository(Organization::class)->findEpcis([]);

        $epcis_with_org = [];
        $communes_perimeters = [];

        foreach ($organizations_epcis as $organization) {
            // Cache commune perimeters for a given perimeter id.
            $perimeter_id = $organization["perimeter__id"];
            if (array_key_exists($perimeter_id, $communes_perimeters)) {
                $perimeters = $communes_perimeters[$perimeter_id];
            } else {
                $perimeters = $this->managerRegistry->getRepository(Perimeter::class)->findCommunesContained([
                    'idParent' => $perimeter_id,
                    'scale' => Perimeter::SCALE_COMMUNE
                ]);
                $communes_perimeters[$perimeter_id] = $perimeters;
            }

            foreach ($perimeters as $perimeter) {
                $key = $perimeter["code"] . "-" . $perimeter["name"];
                $content = [
                    "organization_name" => $organization["name"],
                    "user_email" => $organization["user__email"],
                    "projects_count" => $organization["projects_count"],
                    "date_created" => $organization["dateCreate"]->format("Y-m-d"),
                    "age" => 4,
                ];
                if (array_key_exists($key, $epcis_with_org)) {
                    $already_exists = false;
                    foreach ($epcis_with_org[$key] as &$epci) {
                        if ($epci["organization_name"] == $organization["name"]) {
                            $already_exists = true;
                            $epci["user_email"] = $epci["user_email"] . ", " . $organization["user__email"];
                            $epci["projects_count"] += $organization["projects_count"];
                        }
                    }
                    if (!$already_exists) {
                        $epcis_with_org[$key][] = $content;
                    }
                } else {
                    $epcis_with_org[$key][] = $content;
                }
            }
            unset($perimeters);
        }
        unset($organizations_epcis);

        // fil arianne
        $this->breadcrumb->add(
            'Dashboard',
            $this->generateUrl('admin_statistics_dashboard')
        );
        $this->breadcrumb->add(
            'Statistiques sur carte'
        );

        // rendu template
        return $this->render('admin/statistics/carto.html.twig', [
            'regions_geojson' => $regionsGeojson,
            'regions_org_counts' => json_encode($regionsOrgCounts),
            'regions_org_communes_max' => $regionsOrgCommunesMax,
            'departments_codes' => $countiesCode,
            'departments_org_communes_max' => $countiesOrgCommunesMax,
            'departments_org_counts' => json_encode($countiesOrgCounts),
            'communes_with_org' => json_encode($communes_with_org),
            'epcis_with_org' => json_encode($epcis_with_org),
            'project_dir' => $kernelInterface->getProjectDir()
        ]);
    }

    private function getNbCommuneByDepartmentOffical(string $departmentCode): int
    {
        $nbCommunes = [
            "01" => 393,
            "02" => 799,
            "03" => 317,
            "04" => 198,
            "05" => 162,
            "06" => 163,
            "07" => 335,
            "08" => 449,
            "09" => 327,
            "10" => 431,
            "11" => 433,
            "12" => 285,
            "13" => 119,
            "14" => 528,
            "15" => 246,
            "16" => 364,
            "17" => 463,
            "18" => 287,
            "19" => 279,
            "2A" => 124,
            "2B" => 236,
            "21" => 698,
            "22" => 348,
            "23" => 256,
            "24" => 503,
            "25" => 571,
            "26" => 363,
            "27" => 585,
            "28" => 365,
            "29" => 277,
            "30" => 351,
            "31" => 586,
            "32" => 461,
            "33" => 535,
            "34" => 342,
            "35" => 333,
            "36" => 241,
            "37" => 272,
            "38" => 512,
            "39" => 494,
            "40" => 327,
            "41" => 267,
            "42" => 323,
            "43" => 257,
            "44" => 207,
            "45" => 325,
            "46" => 313,
            "47" => 319,
            "48" => 152,
            "49" => 177,
            "50" => 446,
            "51" => 613,
            "52" => 426,
            "53" => 240,
            "54" => 591,
            "55" => 499,
            "56" => 249,
            "57" => 725,
            "58" => 309,
            "59" => 648,
            "60" => 679,
            "61" => 385,
            "62" => 890,
            "63" => 464,
            "64" => 546,
            "65" => 469,
            "66" => 226,
            "67" => 514,
            "68" => 366,
            "69" => 208,
            "69M" => 59,
            "70" => 539,
            "71" => 565,
            "72" => 354,
            "73" => 273,
            "74" => 279,
            "75" => 1,
            "76" => 708,
            "77" => 507,
            "78" => 259,
            "79" => 256,
            "80" => 772,
            "81" => 314,
            "82" => 195,
            "83" => 153,
            "84" => 151,
            "85" => 257,
            "86" => 266,
            "87" => 195,
            "88" => 507,
            "89" => 423,
            "90" => 101,
            "91" => 194,
            "92" => 36,
            "93" => 40,
            "94" => 47,
            "95" => 184,
            "971" => 32,
            "972" => 34,
            "973" => 22,
            "974" => 24,
            "976" => 17,
        ];

        return isset($nbCommunes[$departmentCode]) ? $nbCommunes[$departmentCode] : 0;
    }

    public function getAge(\DateTime $date): int
    {
        $now = new \DateTime();
        $interval = $now->diff($date);

        if ($interval->days < 30) {
            return 3;
        } elseif ($interval->days < 90) {
            return 2;
        } else {
            return 1;
        }
    }


    #[Route('/admin/statistics/stats-intercommunalites/', name: 'admin_statistics_interco')]
    public function statsInterco(): Response
    {
        $interco_types = [];

        foreach (Organization::INTERCOMMUNALITY_TYPES as $intercoType) {
            $key = $intercoType['slug'];
            $label = $intercoType['name'];
            # Saving some space on the label
            $label = str_replace(["Communauté"], ["Comm."], $label);


            $interco_type_dict = [
                "code" => $key,
                "div_id" => "#chart-" . strtolower($key),
                "label" => $label,
            ];

            $interco_type_dict["total"] = Organization::TOTAL_BY_INTERCOMMUNALITY_TYPE[$key];
            $organizationRepository = $this->managerRegistry->getRepository(Organization::class);
            $interco_type_dict["current"] = $organizationRepository->countInterco([
                'intercommunalityType' => $key,
            ]);

            $interco_type_dict["percentage"] = round(
                $interco_type_dict["current"] * 100 / $interco_type_dict["total"],
                1
            );

            # Prevent the chart to "overflow"
            if ($interco_type_dict["current"] > $interco_type_dict["total"]) {
                $interco_type_dict["current_chart"] = $interco_type_dict["total"];
            } else {
                $interco_type_dict["current_chart"] = $interco_type_dict["current"];
            }

            $interco_types[] = $interco_type_dict;
        }

        $charts = [];

        foreach ($interco_types as $interco_type) {

            $chart = $this->getChartObjectif($interco_type['current_chart'], $interco_type['total'], null);

            $charts[$interco_type['code']] = $chart;
        }

        // on refait le tableau par code
        $interco_types_final = [];
        foreach ($interco_types as $interco_type) {
            $interco_types_final[$interco_type['code']] = $interco_type;
        }

        // fil arianne
        $this->breadcrumb->add(
            'Dashboard',
            $this->generateUrl('admin_statistics_dashboard')
        );
        $this->breadcrumb->add(
            'Statistiques Intercommunalités'
        );

        // rendu template
        return $this->render('admin/statistics/interco.html.twig', [
            'interco_types' => $interco_types_final,
            'charts' => $charts
        ]);
    }

    #[Route('/admin/statistics/stats-projets/', name: 'admin_statistics_projects')]
    public function statsProjects(
        AdminContext $adminContext
    ): Response {

        // dates par défaut
        $dateMin = new \DateTime('-1 month');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class, null, ['method' => 'GET']);
        $formDateRange->handleRequest($adminContext->getRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        // gestion pagination
        $currentPage = (int) $adminContext->getRequest()->get('page', 1);


        // les projets
        $projects = $this->managerRegistry->getRepository(Project::class)->getQueryBuilder([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'orderBy' => [
                'sort' => 'p.dateCreate',
                'order' => 'DESC'
            ]
        ]);
        $adapter = new QueryAdapter($projects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_PROJECT_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        // fil arianne
        $this->breadcrumb->add(
            'Dashboard',
            $this->generateUrl('admin_statistics_dashboard')
        );
        $this->breadcrumb->add(
            'Statistiques projets'
        );

        // rendu template
        return $this->render('admin/statistics/projects.html.twig', [
            'formDateRange' => $formDateRange,
            'projects' => $projects,
            'myPager' => $pagerfanta,
        ]);
    }
}
