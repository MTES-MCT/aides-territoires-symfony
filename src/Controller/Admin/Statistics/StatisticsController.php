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
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use FontLib\Table\Type\name;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends DashboardController
{
    private function getChartEcpi($nbEcpi, $nbEcpiTotal, $nbEcpiObjectif): Chart
    {
        $chartEcpi = $this->chartBuilderInterface->createChart(Chart::TYPE_BAR);
        $chartEcpi->setData([
            'labels' => ['Ecpi'],
            'datasets' => [
                [
                    'label' => 'Inscrites',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'data' => [$nbEcpi],
                ],
                [
                    'label' => 'Total',
                    'backgroundColor' => 'rgb(75, 192, 192)',
                    'data' => [$nbEcpiTotal],
                ],
            ],
        ]);
        
        $chartEcpi->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => true
                ],
                'x' => [
                    'stacked' => true
                ]
            ],
            'plugins' => [
                'annotation' => [
                    'annotations' => [
                        'line1' => [
                            'type' => 'line',
                            'yMin' => $nbEcpiObjectif,
                            'yMax' => $nbEcpiObjectif,
                            'borderColor' => 'rgb(54, 162, 235)',
                            'borderWidth' => 4,
                            'clip' => false, // add this line
                            'label' => [
                                'enabled' => true,
                                'content' => 'Objectif '.$nbEcpiObjectif,
                                'position' => 'center',
                                'display' => true
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return $chartEcpi;
    }
    private function getChartCommune($nbCommune, $nbCommuneTotal, $nbCommuneObjectif): Chart
    {
        $chartCommune = $this->chartBuilderInterface->createChart(Chart::TYPE_BAR);

        $chartCommune->setData([
            'labels' => ['Communes'],
            'datasets' => [
                [
                    'label' => 'Inscrites',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'data' => [$nbCommune],
                ],
                [
                    'label' => 'Total',
                    'backgroundColor' => 'rgb(75, 192, 192)',
                    'data' => [$nbCommuneTotal],
                ],
            ],
        ]);
        
        $chartCommune->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => true,
                ],
                'x' => [
                    'stacked' => true
                ]
            ],
            'plugins' => [
                'annotation' => [
                    'annotations' => [
                        'line1' => [
                            'type' => 'line',
                            'yMin' => $nbCommuneObjectif,
                            'yMax' => $nbCommuneObjectif,
                            'borderColor' => 'rgb(54, 162, 235)',
                            'borderWidth' => 4,
                            'clip' => false, // add this line
                            'label' => [
                                'enabled' => true,
                                'content' => 'Objectif '.$nbCommuneObjectif,
                                'position' => 'center',
                                'display' => true
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return $chartCommune;
    }

    private function getStatsGlobal(): array
    {
        return [
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
            'nbCommuneObjectif' => 10000,
            'nbEcpi' => $this->managerRegistry->getRepository(Organization::class)->countEcpi([]),
            'nbEcpiTotal' => 1256,
            'nbEcpiObjectif' => (int) 1256 * 0.75,
            'nbEcpiObjectifPercentage' => round((1256 * 0.75 / 1256) * 100, 1),
            'nbEcpiPercentage' => round((1256 / 1256) * 100, 1),
        ];
    }

    #[Route('/admin/statistics/dashboard', name: 'admin_statistics_dashboard')]
    public function dashboard(
        AdminContext $adminContext,
        MatomoService $matomoService
    ): Response
    {
        $statsGlobal = $this->getStatsGlobal();
        $chartCommune = $this->getChartCommune($statsGlobal['nbCommune'], $statsGlobal['nbCommuneTotal'], $statsGlobal['nbCommuneObjectif']);
        $chartEcpi = $this->getChartEcpi($statsGlobal['nbEcpi'], $statsGlobal['nbEcpiTotal'], $statsGlobal['nbEcpiObjectif']);

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
            apiMethod:'VisitsSummary.get',
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
            $labels[] = $dateStart->format('d/m/Y').' au '.$dateEnd->format('d/m/Y');
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
            'chartEcpi' => $chartEcpi,

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
    ): Response
    {
        $statsGlobal = $this->getStatsGlobal();
        $chartCommune = $this->getChartCommune($statsGlobal['nbCommune'], $statsGlobal['nbCommuneTotal'], $statsGlobal['nbCommuneObjectif']);
        $chartEcpi = $this->getChartEcpi($statsGlobal['nbEcpi'], $statsGlobal['nbEcpiTotal'], $statsGlobal['nbEcpiObjectif']);

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
        foreach($userRegistersByDate as $date => $nbRegister) {
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
            'chartEcpi' => $chartEcpi,
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
    ): Response
    {
        $statsGlobal = $this->getStatsGlobal();
        $chartCommune = $this->getChartCommune($statsGlobal['nbCommune'], $statsGlobal['nbCommuneTotal'], $statsGlobal['nbCommuneObjectif']);
        $chartEcpi = $this->getChartEcpi($statsGlobal['nbEcpi'], $statsGlobal['nbEcpiTotal'], $statsGlobal['nbEcpiObjectif']);

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
            apiMethod:'VisitsSummary.get',
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
            
            $labels[] = $dateStart->format('d/m/Y').' au '.$dateEnd->format('d/m/Y');
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
            ]);;
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
            apiMethod:'Actions.getPageUrls',
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
                'isEcpi' => true,
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
            'chartEcpi' => $chartEcpi,
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
    ): Response
    {
        $statsGlobal = $this->getStatsGlobal();
        $chartCommune = $this->getChartCommune($statsGlobal['nbCommune'], $statsGlobal['nbCommuneTotal'], $statsGlobal['nbCommuneObjectif']);
        $chartEcpi = $this->getChartEcpi($statsGlobal['nbEcpi'], $statsGlobal['nbEcpiTotal'], $statsGlobal['nbEcpiObjectif']);

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


            $labels[] = $startOfWeek->format('d/m/Y').' au '.$endOfWeek->format('d/m/Y');
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
            'chartEcpi' => $chartEcpi,
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
    ): Response
    {
        return $this->render('admin/statistics/dashboard.html.twig', [
        ]);
    }
}