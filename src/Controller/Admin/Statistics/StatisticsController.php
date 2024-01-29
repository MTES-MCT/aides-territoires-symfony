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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends DashboardController
{
    #[Route('/admin/statistics/dashboard', name: 'admin_statistics_dashboard')]
    public function dashboard(
        AdminContext $adminContext,
        MatomoService $matomoService
    ): Response
    {
        $nbUsers = $this->managerRegistry->getRepository(User::class)->count(['isBeneficiary' => true]);
        $nbOrganizations = $this->managerRegistry->getRepository(Organization::class)->count(['isImported' => false]);
        $nbInterco = $this->managerRegistry->getRepository(Organization::class)->countInterco([]);
        $nbProjects = $this->managerRegistry->getRepository(Project::class)->count([]);
        $nbAidProjects = $this->managerRegistry->getRepository(AidProject::class)->countDistinctAids([]);
        $nbAidsLive = $this->managerRegistry->getRepository(Aid::class)->countLives([]);
        $nbBackers = $this->managerRegistry->getRepository(Backer::class)->countWithAids([]);
        $nbSearchPages = $this->managerRegistry->getRepository(SearchPage::class)->count([]);

        $nbCommune = $this->managerRegistry->getRepository(Organization::class)->countCommune([]);
        $nbCommuneTotal =35039;
        $nbCommuneObjectif = 10000;
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
        

        $nbEcpi = $this->managerRegistry->getRepository(Organization::class)->countEcpi([]);
        $nbEcpiTotal = 1256;
        $nbEcpiObjectif = (int) 1256 * 0.75;
        $nbEcpiObjectifPercentage = round(($nbEcpiObjectif / $nbEcpiTotal) * 100, 1);
        $nbEcpiPercentage = round(($nbEcpi / $nbEcpiTotal) * 100, 1);

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


        return $this->render('admin/statistics/dashboard.html.twig', [
            // globale
            'nbUsers' => $nbUsers,
            'nbOrganizations' => $nbOrganizations,
            'nbInterco' => $nbInterco,
            'nbProjects' => $nbProjects,
            'nbAidProjects' => $nbAidProjects,
            'nbAidsLive' => $nbAidsLive,
            'nbBackers' => $nbBackers,
            'nbSearchPages' => $nbSearchPages,
            'nbCommune' => $nbCommune,
            'nbCommuneTotal' => $nbCommuneTotal,
            'chartCommune' => $chartCommune,
            'nbEcpi' => $nbEcpi,
            'nbEcpiTotal' => $nbEcpiTotal,
            'nbEcpiPercentage' => $nbEcpiPercentage,
            'nbEcpiObjectif' => $nbEcpiObjectif,
            'nbEcpiObjectifPercentage' => $nbEcpiObjectifPercentage,
            'chartEcpi' => $chartEcpi,

            // specific
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'statsMatomo' => $statsMatomo[0] ?? [],
            'consultationSelected' => true,
            'acquisitionSelected' => false,
            'engagementSelected' => false,
            'porteursSelected' => false,
            'statsMatomoActions' => $statsMatomoActions[0] ?? [],
            'statsMatomoLast10Weeks' => $statsMatomoLast10Weeks,
            'nbAidViews' => $nbAidViews,
            'nbAidViewsDistinct' => $nbAidViewsDistinct,
            'chartLast10Weeks' => $chartLast10Weeks,
        ]);
    }

    #[Route('/admin/statistics/acquisition/', name: 'admin_statistics_acquisition')]
    public function acquisition(
        AdminContext $adminContext
    ): Response
    {
        return $this->render('admin/statistics/dashboard.html.twig', [
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