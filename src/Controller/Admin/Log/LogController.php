<?php

namespace App\Controller\Admin\Log;

use App\Controller\Admin\DashboardController;
use App\Entity\Log\LogAidApplicationUrlClick;
use App\Entity\Log\LogAidOriginUrlClick;
use App\Form\Admin\Filter\DateRangeType;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

class LogController extends DashboardController
{
    #[Route('/admin/log/aids/logs', name: 'admin_log_aids_logs')]
    public function aids(
        AdminContext $adminContext
    ): Response
    {
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
        
        // Logs candidater aides
        // récupération des données
        $logAidApplicationUrlClicks = $this->managerRegistry->getRepository(LogAidApplicationUrlClick::class)->countOnPeriod([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);
        // remplissage des dates manquantes
        $allDates = $this->fillAllDate($logAidApplicationUrlClicks, $dateMin, $dateMax);

        // création du graphique
        $chartApplication = $this->createChart($allDates, 'Clics candidater aides');

        // Top 10 aides candidater
        $topAidApplicationsUrlClicks = $this->managerRegistry->getRepository(LogAidApplicationUrlClick::class)->countTopAidOnPeriod([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'maxResults' => 10
        ]);
        
        // Logs en savoir plus aides
        $logAidOriginUrlClicks = $this->managerRegistry->getRepository(LogAidOriginUrlClick::class)->countOnPeriod([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);
        // remplissage des dates manquantes
        $allDates = $this->fillAllDate($logAidOriginUrlClicks, $dateMin, $dateMax);

        // création du graphique
        $chartOrigin = $this->createChart($allDates, 'Clics en savoir plus aides');
        
        // Top 10 aides en savoir plus
        $topAidOriginUrlClicks = $this->managerRegistry->getRepository(LogAidOriginUrlClick::class)->countTopAidOnPeriod([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'maxResults' => 10
        ]);
        
        // rendu template
        return $this->render('admin/log/aids.html.twig', [
            'formDateRange' => $formDateRange,
            'chartApplication' => $chartApplication,
            'chartOrigin' => $chartOrigin,
            'topAidApplicationsUrlClicks' => $topAidApplicationsUrlClicks,
            'topAidOriginUrlClicks' => $topAidOriginUrlClicks
        ]);
    }

    public function fillAllDate(array $data, \DateTime $dateMin, \DateTime $dateMax): array
    {
        $dateMin = clone $dateMin;
        $dateMax = clone $dateMax;

        $allDates = [];

        while ($dateMin < $dateMax) {
            $allDates[$dateMin->format('Y-m-d')] = [
                'dateCreate' => $dateMin->format('Y-m-d'),
                'nb' => $this->getDateValueInArray($data, $dateMin)
            ];
            $dateMin->modify('+1 day');
        }
        
        return $allDates;
    }

    public function getDateValueInArray(array $data, \DateTime $date): int
    {
        foreach ($data as $item) {
            if ($item['dateCreate'] == $date) {
                return $item['nb'];
            }
        }
        return 0;
    }

    public function createChart(array $datas, string $chartLabel): Chart
    {
        // création du graphique
        $chartDatasLabels = [];
        $chartDatas = [];

        foreach ($datas as $data) {
            $chartDatasLabels[] = $data['dateCreate'];
            $chartDatas[] = $data['nb'];
        }

        $chart = $this->chartBuilderInterface->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $chartDatasLabels,
            'datasets' => [
                [
                    'label' => $chartLabel,
                    'backgroundColor' => 'rgb(255, 255, 255)',
                    'borderColor' => 'rgb(255, 0, 0)',
                    'data' => $chartDatas,
                ],
            ],
        ]);

        return $chart;
    }
}