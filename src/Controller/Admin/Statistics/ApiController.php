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

        // on fait un tableau de tous les jours de la période, chaque entrée contient chaque heure de la journée
        $days = [];
        $date = clone $dateMin;
        while ($date <= $dateMax) {
            $days[$date->format('Y-m-d')] = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $days[$date->format('Y-m-d')][str_pad($hour, 2, '0', STR_PAD_LEFT)] = [
                    'total' => 0,
                    'types' => [
                        'logAidSearchs' => [
                            'label' => 'Recherche d\'aides',
                            'backgroundColor' => 'rgb(255, 255, 0)',
                            'total' => 0,
                            'organizations' => []
                        ],
                        'logAidViews' => [
                            'label' => 'Détails d\'aides',
                            'backgroundColor' => 'rgb(0, 255, 255)',
                            'total' => 0,
                            'organizations' => []
                        ]
                    ]
                ];
            }
            $date->modify('+1 day');
        }
        
        // les logs de recherche d'aides
        $logAidSearchs = $logAidSearchRepository->countApiByHourByOrganization([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        
        foreach ($logAidSearchs as $logAidSearch) {
            $days[$logAidSearch['dateDay']][$logAidSearch['dateHour']]['total'] += $logAidSearch['nb'];
            $days[$logAidSearch['dateDay']][$logAidSearch['dateHour']]['types']['logAidSearchs']['total'] += $logAidSearch['nb'];
            if (isset($days[$logAidSearch['dateDay']][$logAidSearch['dateHour']]['types']['logAidSearchs']['organizations'][$logAidSearch['organizationId']])) {
                $days[$logAidSearch['dateDay']][$logAidSearch['dateHour']]['types']['logAidSearchs']['organizations'][$logAidSearch['organizationId']]['total'] += $logAidSearch['nb'];
            } else {
                $days[$logAidSearch['dateDay']][$logAidSearch['dateHour']]['types']['logAidSearchs']['organizations'][$logAidSearch['organizationId']] = [
                    'total' => $logAidSearch['nb'],
                    'name' => $logAidSearch['organizationName'],
                ];
            }
        }

        // les logs de vues détails aide
        $logAidViews = $logAidViewRepository->countApiByHourByOrganization([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);
        foreach ($logAidViews as $logAidView) {
            $days[$logAidView['dateDay']][$logAidView['dateHour']]['total'] += $logAidView['nb'];
            $days[$logAidView['dateDay']][$logAidView['dateHour']]['types']['logAidViews']['total'] += $logAidView['nb'];
            if (isset($days[$logAidView['dateDay']][$logAidView['dateHour']]['types']['logAidViews']['organizations'][$logAidView['organizationId']])) {
                $days[$logAidView['dateDay']][$logAidView['dateHour']]['types']['logAidViews']['organizations'][$logAidView['organizationId']]['total'] += $logAidView['nb'];
            } else {
                $days[$logAidView['dateDay']][$logAidView['dateHour']]['types']['logAidViews']['organizations'][$logAidView['organizationId']] = [
                    'total' => $logAidView['nb'],
                    'name' => $logAidView['organizationName'],
                ];
            }
        }

        
        // graphique utilisation totale, jour par jour, stacked sur type utilisation
        $labels = [];
        foreach ($days as $day => $hours) {
            $labels[] = $day;
        }
        $datasetsOfTypes = [];
        // parcours les jours
        foreach ($days as $day => $hours) {
            $typesOfDay = [];
            // parcours heure par heure
            foreach ($hours as $hour) {
                // recupère les différents types d'utilisation
                foreach ($hour['types'] as $typeSlug => $type) {
                    if (!isset($typesOfDay[$typeSlug])) {
                        $typesOfDay[$typeSlug] = [
                            'label' => $type['label'],
                            'backgroundColor' => $type['backgroundColor'],
                            'total' => 0,
                        ];
                    }
                    $typesOfDay[$typeSlug]['total'] += $type['total'];
                }
            }

            // les aujoutes au dataset des types
            foreach ($typesOfDay as $typeSlug => $typeOfDay) {
                if (!isset($datasetsOfTypes[$typeSlug])) {
                    $datasetsOfTypes[$typeSlug] = [
                        'label' => $typeOfDay['label'],
                        'backgroundColor' => $typeOfDay['backgroundColor'],
                        'data' => [],
                    ];
                }
                $datasetsOfTypes[$typeSlug]['data'][] = $typeOfDay['total'];
            }
        }

        // on construit les datasets du graphique à partir de ceux du site
        $datasets = [];
        foreach ($datasetsOfTypes as $dataset) {
            $datasets[] = $dataset;
        }

        $chartTotal = $chartBuilderInterface->createChart(Chart::TYPE_BAR);
        $chartTotal->setData([
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
        $chartTotal->setOptions($options);

        // utilisation de l'api heure par heure
        $chartByHour = $chartBuilderInterface->createChart(Chart::TYPE_LINE);
        $labels = [];
        foreach ($days as $day => $hours) {
            foreach ($hours as $keyHour => $hour) {
                $labels[] = ($keyHour == '00') ? $day.'-'.$keyHour : $keyHour;
            }
        }

        $datasetTypes = [];
        foreach ($days as $day => $hours) {
            // parcours heure par heure
            foreach ($hours as $keyHour => $hour) {
                // recupère les différents types d'utilisation
                foreach ($hour['types'] as $typeSlug => $type) {
                    if (!isset($datasetTypes[$typeSlug])) {
                        $datasetTypes[$typeSlug] = [
                            'label' => $type['label'],
                            'backgroundColor' => $type['backgroundColor'],
                            'data' => [],
                        ];
                    }
                    if (!isset($datasetTypes[$typeSlug]['data'][$day.'-'.$keyHour])) {
                        $datasetTypes[$typeSlug]['data'][$day.'-'.$keyHour] = [];
                        for ($i = 0; $i < 24; $i++) {
                            $datasetTypes[$typeSlug]['data'][$day.'-'.str_pad($i, 2, '0', STR_PAD_LEFT)] = 0;
                        }
                    }
                    $datasetTypes[$typeSlug]['data'][$day.'-'.$keyHour] += $type['total'];
                }
            }
        }
        // on construit les datasets du graphique à partir de celui des types
        $datasets = [];
        foreach ($datasetTypes as $dataset) {
            $datasets[] = $dataset;
        }

        $chartByHour->setData([
            'labels' => $labels,
            'datasets' => $datasets,
        ]);

        // rendu template
        return $this->render('admin/statistics/api/api_use.html.twig', [
            'formDateRange' => $formDateRange->createView(),
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'chartTotal' => $chartTotal,
            'chartByHour' => $chartByHour
        ]);
    }
}