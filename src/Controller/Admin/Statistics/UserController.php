<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Aid\AidProject;
use App\Entity\Log\LogUserLogin;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class UserController extends AbstractController
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ChartBuilderInterface $chartBuilderInterface,
    )
    {   
    }

    #[Route('/admin/statistics/user/dashboard', name: 'admin_statistics_user_dashboard')]
    public function communeDashboard(
    ): Response
    {
        // les fréquences de connexions
        $nbUsers = $this->managerRegistry->getRepository(User::class)->countCustom();
        $nbLoggedAtLeastOnce = $this->managerRegistry->getRepository(LogUserLogin::class)->countUsersLoggedAtLeastOnce();
        $percentNbLoggedAtLeastOnce = $nbUsers > 0 ? round($nbLoggedAtLeastOnce / $nbUsers * 100, 2) : 0;
        $uniqueLoginsByYear = $this->managerRegistry->getRepository(LogUserLogin::class)->getUniqueLoginsByYear();
        $uniqueLoginsByQuarter = $this->managerRegistry->getRepository(LogUserLogin::class)->getUniqueLoginsByQuarters();
        $uniqueLoginsByMonth = $this->managerRegistry->getRepository(LogUserLogin::class)->getUniqueLoginsByMonth();
        $uniqueLoginsByWeek = $this->managerRegistry->getRepository(LogUserLogin::class)->getUniqueLoginsByWeek();
        $nbUsersLoggedOnce = $this->managerRegistry->getRepository(LogUserLogin::class)->countUsersLoggedOnce();
        $percentNbUsersLoggedOnce = $nbUsers > 0 ? round($nbUsersLoggedOnce / $nbUsers * 100, 2) : 0;
        dump($nbUsers, $nbLoggedAtLeastOnce, $uniqueLoginsByQuarter, $uniqueLoginsByMonth, $uniqueLoginsByWeek, $nbUsersLoggedOnce);

        // tableau connexion par année pour pourcentage
        $loginsByYear = [];
        foreach ($uniqueLoginsByYear as $uniqueLoginsByYearItem) {
            $loginsByYear[$uniqueLoginsByYearItem['year']] = $uniqueLoginsByYearItem['unique_users'];
        }

        // chart by quarter
        $labels = [];
        $datas = [];
        foreach ($uniqueLoginsByQuarter as $uniqueLoginsByQuarterItem) {
            $labels[] = $uniqueLoginsByQuarterItem['year'] . '-' . $uniqueLoginsByQuarterItem['quarter'];
            $datas[] = $uniqueLoginsByQuarterItem['unique_users'];
        }
        $chartLoginsByQuarter = $this->getLineChart($labels, $datas, 'Evolution des connexions par trimestre');

        // chart by month
        $labels = [];
        $datas = [];
        foreach ($uniqueLoginsByMonth as $uniqueLoginsByMonthItem) {
            $labels[] = $uniqueLoginsByMonthItem['year'] . '-' . $uniqueLoginsByMonthItem['month'];
            $datas[] = $uniqueLoginsByMonthItem['unique_users'];
        }
        $chartLoginsByMonth = $this->getLineChart($labels, $datas, 'Evolution des connexions par mois');

        // chart by week
        $labels = [];
        $datas = [];
        foreach ($uniqueLoginsByWeek as $uniqueLoginsByWeekItem) {
            $labels[] = $uniqueLoginsByWeekItem['year'] . '-' . $uniqueLoginsByWeekItem['week'];
            $datas[] = $uniqueLoginsByWeekItem['unique_users'];
        }
        $chartLoginsByWeek = $this->getLineChart($labels, $datas, 'Evolution des connexions par semaine');
        
        // retour template
        return $this->render('admin/statistics/user/dashboard.html.twig', [
            'nbUsers' => $nbUsers,
            'nbLoggedAtLeastOnce' => $nbLoggedAtLeastOnce,
            'percentNbLoggedAtLeastOnce' => $percentNbLoggedAtLeastOnce,
            'nbUsersLoggedOnce' => $nbUsersLoggedOnce,
            'percentNbUsersLoggedOnce' => $percentNbUsersLoggedOnce,
            'chartLoginsByQuarter' => $chartLoginsByQuarter,
            'chartLoginsByMonth' => $chartLoginsByMonth,
            'chartLoginsByWeek' => $chartLoginsByWeek
        ]);
    }


    private function getLineChart(array $labels, array $datas, string $title): Chart
    {
        $chart = $this->chartBuilderInterface->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Connexions',
                    'backgroundColor' => 'rgb(255, 255, 255)',
                    'borderColor' => 'rgb(255, 0, 0)',
                    'data' => $datas,
                ],
            ],
        ]);
        $chart->setOptions([
            'maintainAspectRatio' => true,
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => $title,
                    'font' => [
                        'size' => 24,
                    ],
                ],
            ],
        ]);

        return $chart;
    }

}