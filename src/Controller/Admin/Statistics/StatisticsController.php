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
use App\Repository\Aid\AidProjectRepository;
use App\Repository\Aid\AidRepository;
use App\Repository\Alert\AlertRepository;
use App\Repository\Backer\BackerRepository;
use App\Repository\Log\LogAidApplicationUrlClickRepository;
use App\Repository\Log\LogAidContactClickRepository;
use App\Repository\Log\LogAidOriginUrlClickRepository;
use App\Repository\Log\LogAidSearchRepository;
use App\Repository\Log\LogAidViewRepository;
use App\Repository\Log\LogUserLoginRepository;
use App\Repository\Organization\OrganizationRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\Search\SearchPageRepository;
use App\Repository\User\UserRepository;
use App\Service\Matomo\MatomoService;
use App\Service\User\UserService;
use App\Service\Various\Breadcrumb;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends DashboardController
{
    public const NB_USER_BY_PAGE = 50;
    public const NB_ORGANIZATION_BY_PAGE = 50;
    public const NB_PROJECT_BY_PAGE = 50;

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

    /**
     * Undocumented function
     *
     * @return array<string, mixed>
     */
    private function getStatsGlobal(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        /** @var OrganizationRepository $organizationRepository */
        $organizationRepository = $this->managerRegistry->getRepository(Organization::class);
        /** @var ProjectRepository $projectRepository */
        $projectRepository = $this->managerRegistry->getRepository(Project::class);
        /** @var AidProjectRepository $aidProjectRepository */
        $aidProjectRepository = $this->managerRegistry->getRepository(AidProject::class);
        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);
        /** @var BackerRepository $backerRepository */
        $backerRepository = $this->managerRegistry->getRepository(Backer::class);
        /** @var SearchPageRepository $searchPageRepository */
        $searchPageRepository = $this->managerRegistry->getRepository(SearchPage::class);

        $stats = [
            'nbUsers' => $userRepository->count(['isBeneficiary' => true]),
            'nbOrganizations' => $organizationRepository->count(['isImported' => false]),
            'nbInterco' => $organizationRepository->countInterco([]),
            'nbProjects' => $projectRepository->count([]),
            'nbAidProjects' => $aidProjectRepository->countDistinctAids([]),
            'nbAidsLive' => $aidRepository->countLives([]),
            'nbBackers' => $backerRepository->countWithAids([]),
            'nbSearchPages' => $searchPageRepository->count([]),
            'nbCommune' => $organizationRepository->countCommune([]),
            'nbCommuneTotal' => 35039,
            'nbCommuneObjectif' => 15000,
            'nbEpci' => $organizationRepository->countEpci([]),
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
        $chartCommune = $this->getChartObjectif(
            $statsGlobal['nbCommune'],
            $statsGlobal['nbCommuneTotal'],
            $statsGlobal['nbCommuneObjectif']
        );
        $chartEpci = $this->getChartObjectif(
            $statsGlobal['nbEpci'],
            $statsGlobal['nbEpciTotal'],
            $statsGlobal['nbEpciObjectif']
        );

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

        // vues des aides (totales et distinces)
        // on filtre sur le label pour récupérer le groupement aides / autres de matomo
        $statsAidsViews = $matomoService->getMatomoStats(
            apiMethod: MatomoService::MATOMO_GET_PAGE_URLS_API_METHOD,
            fromDateString: $dateMin->format('Y-m-d'),
            toDateString: $dateMax->format('Y-m-d'),
            options: [
                'flat' => 1,
                'filter_column' => 'label',
                'filter_pattern' => 'aides'
            ],
        );

        $nbAidVisits = 0;
        $nbAidViews = 0;
        $nbAids = 0;
        foreach ($statsAidsViews as $statsAidsView) {
            if ($statsAidsView->label == '/aides/') {
                continue;
            }
            $nbAidVisits += $statsAidsView->nb_visits;
            $nbAidViews += $statsAidsView->nb_hits;
            $nbAids++;
        }

        $labels = [];
        $visitsUnique = [];
        $registers = [];
        $alerts = [];

        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        /** @var AlertRepository $alertRepository */
        $alertRepository = $this->managerRegistry->getRepository(Alert::class);

        foreach ($statsMatomoLast10Weeks as $key => $stats) {
            $dates = explode(',', $key);
            $dateStart = new \DateTime($dates[0]);
            $dateEnd = new \DateTime($dates[1]);

            $labels[] = $dateStart->format('d/m/Y') . ' au ' . $dateEnd->format('d/m/Y');
            $visitsUnique[] = $stats[0]->nb_uniq_visitors ?? 0;
            $registers[] = $userRepository
                ->countRegisters(['dateCreateMin' => $dateStart, 'dateCreateMax' => $dateMax]);
            $alerts[] = $alertRepository
                ->countCustom(['dateCreateMin' => $dateStart, 'dateCreateMax' => $dateMax]);
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
            'chartLast10Weeks' => $chartLast10Weeks,
            'nbAidVisits' => $nbAidVisits,
            'nbAidViews' => $nbAidViews,
            'nbAids' => count($statsAidsViews),
            'consultationSelected' => true,
        ]);
    }

    #[Route('/admin/statistics/acquisition/', name: 'admin_statistics_acquisition')]
    public function acquisition(
        AdminContext $adminContext,
        MatomoService $matomoService
    ): Response {
        $statsGlobal = $this->getStatsGlobal();
        $chartCommune = $this->getChartObjectif(
            $statsGlobal['nbCommune'],
            $statsGlobal['nbCommuneTotal'],
            $statsGlobal['nbCommuneObjectif']
        );
        $chartEpci = $this->getChartObjectif(
            $statsGlobal['nbEpci'],
            $statsGlobal['nbEpciTotal'],
            $statsGlobal['nbEpciObjectif']
        );

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

        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);

        $userRegisters = $userRepository->findCustom([
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
        $chartCommune = $this->getChartObjectif(
            $statsGlobal['nbCommune'],
            $statsGlobal['nbCommuneTotal'],
            $statsGlobal['nbCommuneObjectif']
        );
        $chartEpci = $this->getChartObjectif(
            $statsGlobal['nbEpci'],
            $statsGlobal['nbEpciTotal'],
            $statsGlobal['nbEpciObjectif']
        );

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

        /** @var LogUserLoginRepository $logUserLoginRepository */
        $logUserLoginRepository = $this->managerRegistry->getRepository(LogUserLogin::class);
        /** @var LogAidSearchRepository $logAidSearchRepository */
        $logAidSearchRepository = $this->managerRegistry->getRepository(LogAidSearch::class);
        /** @var AlertRepository $alertRepository */
        $alertRepository = $this->managerRegistry->getRepository(Alert::class);

        // nb connexions
        $nbLogins = $logUserLoginRepository->countCustom([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'distinctUser' => true
        ]);
        $nbAidSearch = $logAidSearchRepository->countCustom([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbAlerts = $alertRepository->countCustom([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);

        /** @var LogAidOriginUrlClickRepository $logAidOriginUrlClickRepository */
        $logAidOriginUrlClickRepository = $this->managerRegistry->getRepository(LogAidOriginUrlClick::class);
        $nbInformationsTotal = $logAidOriginUrlClickRepository->countCustom([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        /** @var LogAidApplicationUrlClickRepository $logAidApplicationUrlClickRepository */
        $logAidApplicationUrlClickRepository = $this->managerRegistry->getRepository(LogAidApplicationUrlClick::class);
        $nbApplicationsTotal = $logAidApplicationUrlClickRepository->countCustom([
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
        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);

        foreach ($statsMatomoLast10Weeks as $key => $stats) {
            $dates = explode(',', $key);
            $dateStart = new \DateTime($dates[0]);
            $dateEnd = new \DateTime($dates[1]);

            $labels[] = $dateStart->format('d/m/Y') . ' au ' . $dateEnd->format('d/m/Y');
            $registers[] = $userRepository->countRegisters([
                'dateCreateMin' => $dateStart,
                'dateCreateMax' => $dateMax,

            ]);
            $registersWithAid[] = $userRepository->countRegistersWithAid([
                'dateCreateMin' => $dateStart,
                'dateCreateMax' => $dateMax,

            ]);
            $registersWithProject[] = $userRepository->countRegistersWithProject([
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
                'filter_column' => 'url',
                'filter_limit' => 100 * 1.3,
                'filter_pattern' => MatomoService::REGEXP_AID_URL
            ]
        );

        $topAids = [];
        /** @var LogAidContactClickRepository $logAidContactClickRepository */
        $logAidContactClickRepository = $this->managerRegistry->getRepository(LogAidContactClick::class);
        /** @var LogAidOriginUrlClickRepository $logAidOriginUrlClickRepository */
        $logAidOriginUrlClickRepository = $this->managerRegistry->getRepository(LogAidOriginUrlClick::class);
        /** @var LogAidApplicationUrlClickRepository $logAidApplicationUrlCLickRepository */
        $logAidApplicationUrlCLickRepository = $this->managerRegistry->getRepository(LogAidApplicationUrlClick::class);

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
                $nbContactClicks = $logAidContactClickRepository->countCustom([
                    'dateMin' => $dateMin,
                    'dateMax' => $dateMax,
                    'aid' => $aid
                ]);
                $nbInformations = $logAidOriginUrlClickRepository->countCustom([
                    'dateMin' => $dateMin,
                    'dateMax' => $dateMax,
                    'aid' => $aid
                ]);
                $nbApplications = $logAidApplicationUrlCLickRepository->countCustom([
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
        /** @var LogUserLoginRepository $logUserLoginRepository */
        $logUserLoginRepository = $this->managerRegistry->getRepository(LogUserLogin::class);
        while ($currentDate > $dateEnd) {
            // user connectés
            $nbUsersConnected = $logUserLoginRepository->countCustom([
                'month' => $currentDate,
                'distinctUser' => true,
                'excludeAdmins' => true
            ]);
            $nbCommunesConnected = $logUserLoginRepository->countCustom([
                'month' => $currentDate,
                'distinctUser' => true,
                'isCommune' => true,
                'excludeAdmins' => true
            ]);
            $nbEpcisConnected = $logUserLoginRepository->countCustom([
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
            'nbInformationsTotal' => $nbInformationsTotal,
            'nbApplicationsTotal' => $nbApplicationsTotal,
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
        $chartCommune = $this->getChartObjectif(
            $statsGlobal['nbCommune'],
            $statsGlobal['nbCommuneTotal'],
            $statsGlobal['nbCommuneObjectif']
        );
        $chartEpci = $this->getChartObjectif(
            $statsGlobal['nbEpci'],
            $statsGlobal['nbEpciTotal'],
            $statsGlobal['nbEpciObjectif']
        );

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

        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        /** @var OrganizationRepository $organizationRepository */
        $organizationRepository = $this->managerRegistry->getRepository(Organization::class);
        /** @var ProjectRepository $projectRepository */
        $projectRepository = $this->managerRegistry->getRepository(Project::class);
        /** @var AidProjectRepository $aidProjectRepository */
        $aidProjectRepository = $this->managerRegistry->getRepository(AidProject::class);
        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        $nbBeneficiaries = $userRepository->countBeneficiaries([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbOrganizationWithBeneficiary = $organizationRepository->countWithUserBeneficiary([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'isImported' => false,
        ]);
        $nbProjects = $projectRepository->countCustom([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbAidProjects = $aidProjectRepository->countCustom([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        $nbContributors = $userRepository->countContributors([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbOrganizationWithContributor = $organizationRepository->countWithUserContributor([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'isImported' => false,
        ]);
        $nbAidsLive = $aidRepository->countLives([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $nbBeneficiariesAndContributors = $userRepository->countCustom([
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

        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);

        while ($currentDate < $dateEnd) {
            $startOfWeek = (clone $currentDate)->setISODate(
                (int) $currentDate->format('Y'),
                (int) $currentDate->format('W'),
                1
            );
            $endOfWeek = (clone $currentDate)->setISODate(
                (int) $currentDate->format('Y'),
                (int) $currentDate->format('W'),
                7
            );

            // inscriptions communes
            $nbCommunes = $userRepository->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsCommune' => true,
            ]);

            // inscription communes avec une aide publiée
            $nbCommunesWithAid = $userRepository->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsCommune' => true,
                'organizationHasAid' => true,
            ]);

            // inscription communes avec un projet
            $nbCommunesWithProject = $userRepository->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsCommune' => true,
                'organizationHasProject' => true,
            ]);

            // inscriptions epci
            $nbEpcis = $userRepository->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsEpci' => true,
            ]);

            // inscription epci avec une aide publiée
            $nbEpcisWithAid = $userRepository->countRegisters([
                'dateCreateMin' => $startOfWeek,
                'dateCreateMax' => $endOfWeek,
                'organizationIsEpci' => true,
                'organizationHasAid' => true,
            ]);

            // inscription epci avec un projet
            $nbEpcisWithProject = $userRepository->countRegisters([
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
        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        $nbUsers = $userRepository->countCustom();
        $nbBeneficiaries = $userRepository->countBeneficiaries();
        $nbContributors = $userRepository->countContributors();
        $nbBeneficiariesAndContributors = $userRepository->countCustom([
            'isBeneficary' => true,
            'isContributor' => true,
        ]);
        $nbBeneficiariesPercent = $nbUsers == 0 ? 0 : round($nbBeneficiaries / $nbUsers * 100, 2);
        $nbContributorsPercent = $nbUsers == 0 ? 0 : round($nbContributors / $nbUsers * 100, 2);
        $nbBeneficiariesAndContributorsPercent = $nbUsers == 0
            ? 0
            : round($nbBeneficiariesAndContributors / $nbUsers * 100, 2);

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
        $users = $userRepository->getQueryBuilder([
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
        /** @var OrganizationRepository $organizationRepository */
        $organizationRepository = $this->managerRegistry->getRepository(Organization::class);
        $nbOrganizations = $organizationRepository->countCustom([
            'isImported' => false,
            'hasPerimeter' => true
        ]);
        $nbOrganizationsByType = $organizationRepository->countByType([
            'isImported' => false,
            'hasPerimeter' => true
        ]);
        foreach ($nbOrganizationsByType as $key => $organizationType) {
            $nbOrganizationsByType[$key]['percent'] = $nbOrganizations == 0
                ? 0
                : round($organizationType['nb'] / $nbOrganizations * 100, 2);
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
        $organizations = $organizationRepository->getQueryBuilder([
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

        /** @var PerimeterRepository $perimeterRepository */
        $perimeterRepository = $this->managerRegistry->getRepository(Perimeter::class);

        // on recupère toutes les régions
        $regions = $perimeterRepository->findCustom([
            'scale' => Perimeter::SCALE_REGION,
            'isObsolete' => false
        ]);

        $regionsOrgCounts = [];
        $regionsOrgCommunesMax = 0;

        /** @var OrganizationRepository $organizationRepository */
        $organizationRepository = $this->managerRegistry->getRepository(Organization::class);

        // on parcours les régions
        /** @var Perimeter $region */
        foreach ($regions as $region) {
            if (!$region->getCode()) {
                continue;
            }

            // on va recupérer les organisations communes de cette région
            $nbCommune = $organizationRepository->countCommune([
                'perimeterRegion' => $region
            ]);

            // on regarde si on change le nombre max de communes pour une région
            $regionsOrgCommunesMax = $nbCommune > $regionsOrgCommunesMax ? $nbCommune : $regionsOrgCommunesMax;

            // on va recupérer les organisations epci de cette région
            $nbEpci = $organizationRepository->countEpci([
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
        $counties = $perimeterRepository->findCustom([
            'scale' => Perimeter::SCALE_COUNTY,
            'isObsolete' => false
        ]);

        $countiesOrgCounts = [];
        $countiesOrgCommunesMax = 0;
        $countiesCode = [];
        // on parcours les départements
        /** @var Perimeter $county */
        foreach ($counties as $county) {
            if (!$county->getCode()) {
                continue;
            }

            // alimente le tableau des codes
            $countiesCode[] = $county->getCode();

            // on va recupérer les organisations communes de cette région
            $nbCommune = $organizationRepository->countCommune([
                'perimeterDepartment' => $county
            ]);

            // on regarde si on change le nombre max de communes pour une région
            $countiesOrgCommunesMax = $nbCommune > $countiesOrgCommunesMax ? $nbCommune : $countiesOrgCommunesMax;

            // on va recupérer les organisations epci de cette région
            $nbEpci = $organizationRepository->countEpci([
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
        $organizationCommunes = $organizationRepository->findCommunes([]);

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
        $organizations_epcis = $organizationRepository->findEpcis([]);

        $epcis_with_org = [];
        $communes_perimeters = [];

        foreach ($organizations_epcis as $organization) {
            // Cache commune perimeters for a given perimeter id.
            $perimeter_id = $organization["perimeter__id"];
            if (array_key_exists($perimeter_id, $communes_perimeters)) {
                $perimeters = $communes_perimeters[$perimeter_id];
            } else {
                $perimeters = $perimeterRepository->findCommunesContained([
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
            /** @var OrganizationRepository $organizationRepository */
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

        /** @var ProjectRepository $projectRepository */
        $projectRepository = $this->managerRegistry->getRepository(Project::class);

        // les projets
        $projects = $projectRepository->getQueryBuilder([
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

    #[Route(
        '/admin/statistics/consultation/ajax/aid-nb-views',
        name: 'admin_statistics_consultation_ajax_aid_nb_views',
        options: ['expose' => true]
    )]
    public function ajaxAidNbViews(
        RequestStack $requestStack
    ): Response {
        try {
            $dateCreateMin = $requestStack->getCurrentRequest()->get('dateCreateMin');
            $dateCreateMax = $requestStack->getCurrentRequest()->get('dateCreateMax');

            $dateCreateMin = new \DateTime(date($dateCreateMin));
            $dateCreateMax = new \DateTime(date($dateCreateMax));

            /** @var LogAidViewRepository $logAidViewRepository */
            $logAidViewRepository = $this->managerRegistry->getRepository(LogAidView::class);

            $nbAidViews = $logAidViewRepository->countAidsViews([
                'dateCreateMin' => $dateCreateMin,
                'dateCreateMax' => $dateCreateMax,
                'notSource' => 'api',
            ]);
            return $this->json(['nbAidViews' => $nbAidViews]);
        } catch (\Exception) {
            return $this->json(['error' => true, 'nbAidViews' => 0], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(
        '/admin/statistics/consultation/ajax/aid-nb-views-distinct',
        name: 'admin_statistics_consultation_ajax_aid_nb_views_distinct',
        options: ['expose' => true]
    )]
    public function ajaxAidNbViewsDistinct(
        RequestStack $requestStack
    ): Response {
        try {
            $dateCreateMin = $requestStack->getCurrentRequest()->get('dateCreateMin');
            $dateCreateMax = $requestStack->getCurrentRequest()->get('dateCreateMax');

            $dateCreateMin = new \DateTime(date($dateCreateMin));
            $dateCreateMax = new \DateTime(date($dateCreateMax));

            /** @var LogAidViewRepository $logAidViewRepository */
            $logAidViewRepository = $this->managerRegistry->getRepository(LogAidView::class);

            $nbAidViewsDistinct = $logAidViewRepository->countAidsViews([
                'dateMin' => $dateCreateMin,
                'dateMax' => $dateCreateMax,
                'notSource' => 'api',
                'distinctAids' => true
            ]);
            return $this->json(['nbAidViewsDistinct' => $nbAidViewsDistinct]);
        } catch (\Exception) {
            return $this->json(['error' => true, 'nbAidViewsDistinct' => 0], Response::HTTP_BAD_REQUEST);
        }
    }
}
