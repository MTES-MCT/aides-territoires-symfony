<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Repository\Backer\BackerRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class BackerController extends DashboardController
{
    #[Route('/admin/statistics/backer/dashboard', name: 'admin_statistics_backer_dashboard')]
    public function backerDashboard(
        BackerRepository $backerRepository,
        ChartBuilderInterface $chartBuilderInterface
    ): Response {
        // nombre total de porteurs
        $nbBackersTotal = $backerRepository->count([]);

        // Les porteurs avec des aides lives
        $backers = $backerRepository->findCustom(
            [
                'nbAidsLiveMin' => 1,
                'orderBy' => [
                    'sort' => 'b.nbAidsLive',
                    'order' => 'DESC'
                ]
            ],
        );

        // pourcentage de backer avec des aides lives
        $percentBackerAidsLive = $nbBackersTotal == 0 ? 0 : number_format((count($backers) * 100 / $nbBackersTotal), 2);

        // graphique
        $chartBackerAids = $chartBuilderInterface->createChart(Chart::TYPE_PIE);

        // pourcentage des 10 premiers backers
        $top10TotalAids = 0;
        $backersTotalAids = 0;
        $current = 0;
        foreach ($backers as $backer) {
            $backersTotalAids += (int) $backer->getNbAidsLive();
            if ($current < 10) {
                $top10TotalAids += (int) $backer->getNbAidsLive();
            }
            $current++;
        }
        $top10Percent = $backersTotalAids == 0 ? 0 : number_format(($top10TotalAids * 100 / $backersTotalAids), 2);

        // moyenne et ecart type de nbAidsLives par backer
        $moyenne = count($backers) == 0 ? 0 : (int) ($backersTotalAids / count($backers));

        // première boucle pour faire les pourcentages
        $total = 0;
        foreach ($backers as $backer) {
            $total += (int) $backer->getNbAidsLive();
        }
        $labels = [];
        $datas = [];
        $colors = [];
        foreach ($backers as $key => $backer) {
            $percentage = $total == 0 ? 0 : number_format(($backer->getNbAidsLive() * 100 / $total), 2);
            $labels[] = $backer->getName() . ' (' . $percentage . '%)';
            $datas[] = $backer->getNbAidsLive();
            $colors[] = 'rgb(' . rand(0, 255) . ', ' . rand(0, 255) . ', ' . rand(0, 255) . ')';
        }

        $chartBackerAids->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nombre d\'aides',
                    'backgroundColor' => $colors,
                    'data' => $datas,
                ],
            ],
        ]);

        $chartBackerAids->setOptions([
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Nombre d\'aides lives par porteur',
                ],
            ],
        ]);



        return $this->render('admin/statistics/backer/dashboard.html.twig', [
            'chartBackerAids' => $chartBackerAids,
            'percentBackerAidsLive' => $percentBackerAidsLive,
            'nbBackersTotal' => $nbBackersTotal,
            'backers' => $backers,
            'backersTotalAids' => $backersTotalAids,
            'top10Percent' => $top10Percent,
            'moyenne' => $moyenne,
        ]);
    }
}
