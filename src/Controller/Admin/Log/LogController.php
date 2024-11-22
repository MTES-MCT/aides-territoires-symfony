<?php

namespace App\Controller\Admin\Log;

use App\Controller\Admin\DashboardController;
use App\Entity\Log\LogAidApplicationUrlClick;
use App\Entity\Log\LogAidOriginUrlClick;
use App\Form\Admin\Filter\DateRangeType;
use App\Repository\Log\LogAidApplicationUrlClickRepository;
use App\Repository\Log\LogAidOriginUrlClickRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

class LogController extends DashboardController
{
    #[Route('/admin/log/aids/logs', name: 'admin_log_aids_logs')]
    public function aids(
        AdminContext $adminContext
    ): Response {
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

        /** @var LogAidApplicationUrlClickRepository $logAidApplicationUrlClickRepository */
        $logAidApplicationUrlClickRepository = $this->managerRegistry->getRepository(LogAidApplicationUrlClick::class);
        // Logs candidater aides
        // récupération des données
        $logAidApplicationUrlClicks = $logAidApplicationUrlClickRepository->countByDay([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        // remplissage des dates manquantes
        $allDates = $this->fillAllDate($logAidApplicationUrlClicks, $dateMin, $dateMax);

        // création du graphique
        $chartApplication = $this->createChart($allDates, 'Clics candidater aides');

        // Top 10 aides candidater
        $topAidApplicationsUrlClicks = $logAidApplicationUrlClickRepository->countTopAidOnPeriod([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'maxResults' => 10
        ]);

        /** @var LogAidOriginUrlClickRepository $logAidOriginUrlClickRepository */
        $logAidOriginUrlClickRepository = $this->managerRegistry->getRepository(LogAidOriginUrlClick::class);

        // Logs en savoir plus aides
        $logAidOriginUrlClicks = $logAidOriginUrlClickRepository->countByDay([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        // remplissage des dates manquantes
        $allDates = $this->fillAllDate($logAidOriginUrlClicks, $dateMin, $dateMax);

        // création du graphique
        $chartOrigin = $this->createChart($allDates, 'Clics en savoir plus aides');

        // Top 10 aides en savoir plus
        $topAidOriginUrlClicks = $logAidOriginUrlClickRepository->countTopAidOnPeriod([
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

    /**
     *
     * @param array<int, array<string, int>> $data
     * @param \DateTime $dateMin
     * @param \DateTime $dateMax
     * @return array<string, array<string, mixed>> $data
     */
    public function fillAllDate(array $data, \DateTime $dateMin, \DateTime $dateMax): array
    {
        $dateMin = clone $dateMin;
        $dateMax = clone $dateMax;

        $allDates = [];

        // transforme en tableau de nb par dateDay
        $keys = array_column($data, 'dateDay');
        $values = array_column($data, 'nb');
        $final = array_combine($keys, $values);

        while ($dateMin <= $dateMax) {
            $allDates[$dateMin->format('Y-m-d')] = [
                'dateCreate' => $dateMin->format('Y-m-d'),
                'nb' => isset($final[$dateMin->format('Y-m-d')]) ? $final[$dateMin->format('Y-m-d')] : 0
            ];
            $dateMin->modify('+1 day');
        }

        return $allDates;
    }

    /**
     *
     * @param array<string, array<string, mixed>> $datas
     * @param string $chartLabel
     * @return Chart
     */
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
        $chart->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ]
        ]);
        return $chart;
    }
}
