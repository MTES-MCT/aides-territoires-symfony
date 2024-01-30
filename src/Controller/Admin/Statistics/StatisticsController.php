<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidProject;
use App\Entity\Alert\Alert;
use App\Entity\Backer\Backer;
use App\Entity\Log\LogAidView;
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

        
        // new Chartist.Line('#chart-nouvelles-inscriptions', {
        //     labels: {{ nb_user_days|safe }},
        //     series: [
        //       {{ nb_user_inscriptions_serie }},
        //     ]
        //   }, {
        //     fullWidth: true,
        //     chartPadding: {
        //       right: 40
        //     },
        //     axisX: {
        //       labelInterpolationFnc: function(value) {
        //         // Only display day/month (from ISO date).
        //         const [ month, day ] = value.slice(5, 10).split('-')
        //         return `${day}/${month}`
        //       }
        //     },
        //     axisY: {
        //       type: Chartist.AutoScaleAxis,
        //       low: 0,
        //       offset: 100
        //     },
        //     plugins: [
        //       Chartist.plugins.tooltip({appendToBody: true})
        //     ]
        //   })

        // dump($referrers);
        // dump($statsMatomoReferer, $statsMatomoRefererAll, $tmp_referrers, $nb_referrers_total, $nb_referrers_total_without_search);
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
        AdminContext $adminContext
    ): Response
    {
        return $this->render('admin/statistics/dashboard.html.twig', [
        ]);
    }

    #[Route('/admin/statistics/porteurs/', name: 'admin_statistics_porteurs')]
    public function porteurs(
        AdminContext $adminContext
    ): Response
    {
        return $this->render('admin/statistics/dashboard.html.twig', [
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