<?php

namespace App\Controller\Admin\Statistics;

use App\Form\Admin\Filter\DateRangeType;
use App\Repository\Log\LogAidSearchRepository;
use App\Repository\Log\LogAidViewRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ApiController extends AbstractController
{
    #[Route('/admin/statistics/api-use', name: 'admin_statistics_api_use')]
    public function apiUse(
        AdminContext $adminContext,
        LogAidSearchRepository $logAidSearchRepository,
        LogAidViewRepository $logAidViewRepository,
        ChartBuilderInterface $chartBuilderInterface
    ) : Response {
        // dates par défaut
        $dateMin = new \DateTime('-1 week');
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

        // on fait un tableau de tous les jours de la période
        $days = [];
        $date = clone $dateMin;
        while ($date <= $dateMax) {
            $days[$date->format('Y-m-d')] = [
                'total' => 0,
                'types' => [
                    'logAidSearchs' => [
                        'label' => 'Recherche d\'aides',
                        'backgroundColor' => 'rgb(255, 255, 0)',
                        'total' => 0,
                    ],
                    'logAidViews' => [
                        'label' => 'Détails d\'aides',
                        'backgroundColor' => 'rgb(0, 255, 255)',
                        'total' => 0,
                    ]
                ]
            ];
            $date->modify('+1 day');
        }
        
        // les logs de recherche d'aides
        $logAidSearchs = $logAidSearchRepository->countApiByDay([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);

        foreach ($logAidSearchs as $logAidSearch) {
            $days[$logAidSearch['dateDay']]['total'] += $logAidSearch['nb'];
            $days[$logAidSearch['dateDay']]['types']['logAidSearchs']['total'] += $logAidSearch['nb'];
        }

        // les logs de vues détails aide
        $logAidViews = $logAidViewRepository->countApiByDay([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        foreach ($logAidViews as $logAidView) {
            $days[$logAidView['dateDay']]['total'] += $logAidView['nb'];
            $days[$logAidView['dateDay']]['types']['logAidViews']['total'] += $logAidView['nb'];
        }

        
        // graphique utilisation totale, jour par jour, stacked sur type utilisation
        $labels = [];
        $datasets = [];
        $datasets['logAidSearchs'] = [
            'label' => 'Recherche d\'aides',
            'backgroundColor' => 'rgb(255, 255, 0)',
            'data' => [],
        ];
        $datasets['logAidViews'] = [
            'label' => 'Détails d\'aides',
            'backgroundColor' => 'rgb(0, 255, 255)',
            'data' => [],
        ];
        foreach ($days as $day => $dayData) {
            $labels[] = $day;
            if (isset($dayData['types']['logAidSearchs'])) {
                $datasets['logAidSearchs']['data'][] = $dayData['types']['logAidSearchs']['total'];
            } else {
                $datasets['logAidSearchs']['data'][] = 0;
            }
            if (isset($dayData['types']['logAidViews'])) {
                $datasets['logAidViews']['data'][] = $dayData['types']['logAidViews']['total'];
            } else {
                $datasets['logAidViews']['data'][] = 0;
            }
        }

        // sort pour transformer les clés
        sort($datasets);

        $chartByType = $chartBuilderInterface->createChart(Chart::TYPE_BAR);
        $chartByType->setData([
            'labels' => $labels,
            'datasets' => $datasets,
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
        $chartByType->setOptions($options);

        // Par organization
        $logAidSearchsByOrganization = $logAidSearchRepository->countByOrganization([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        $logAidViewsByOrganization = $logAidViewRepository->countByOrganization([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);

        // on regroupe tout dans un tableau par id d'organization
        $dataByOrganization = [];
        foreach ($logAidSearchsByOrganization as $logAidSearch) {
            if (!isset($dataByOrganization[$logAidSearch['organizationId']])) {
                $dataByOrganization[$logAidSearch['organizationId']] = [
                    'organizationName' => $logAidSearch['organizationName'],
                    'total' => 0,
                ];
            }
            $dataByOrganization[$logAidSearch['organizationId']]['total'] += $logAidSearch['nb'];
        }
        foreach ($logAidViewsByOrganization as $logAidView) {
            if (!isset($dataByOrganization[$logAidView['organizationId']])) {
                $dataByOrganization[$logAidView['organizationId']] = [
                    'organizationName' => $logAidView['organizationName'],
                    'total' => 0,
                ];
            }
            $dataByOrganization[$logAidView['organizationId']]['total'] += $logAidView['nb'];
        }


        // graphique utilisation par organization
        $labels = [];
        $backgroundColors = [];
        $datas = [];


        foreach ($dataByOrganization as $dataOrganization) {
            $labels[] = $dataOrganization['organizationName'];
            $backgroundColors[] = 'rgb('.rand(0, 255).', '.rand(0, 255).', '.rand(0, 255).')';
            $datas[] = $dataOrganization['total'];
        }

        $datasets = [
            [
                'label' => 'Utilisation',
                'backgroundColor' => $backgroundColors,
                'data' => $datas,
            ]
        ];

        // graphique pie
        $chartByOrganization = $chartBuilderInterface->createChart(Chart::TYPE_PIE);
        $chartByOrganization->setData([
            'labels' => $labels,
            'datasets' => $datasets,
        ]);



        // rendu template
        return $this->render('admin/statistics/api/api_use.html.twig', [
            'formDateRange' => $formDateRange->createView(),
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'chartByType' => $chartByType,
            'chartByOrganization' => $chartByOrganization
        ]);
    }
}