<?php

namespace App\Controller\Admin\Statistics;

use App\Entity\Log\LogUserLogin;
use App\Entity\User\User;
use App\Repository\Log\LogUserLoginRepository;
use App\Repository\User\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class UserController extends AbstractController
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ChartBuilderInterface $chartBuilderInterface,
    ) {
    }

    #[Route('/admin/statistics/user/dashboard', name: 'admin_statistics_user_dashboard')]
    public function communeDashboard(): Response
    {
        // les repository
        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        /** @var LogUserLoginRepository $logUserLoginRepository */
        $logUserLoginRepository = $this->managerRegistry->getRepository(LogUserLogin::class);

        // les fréquences de connexions
        $nbUsers = $userRepository->countCustom();
        $nbLoggedAtLeastOnce = $logUserLoginRepository->countUsersLoggedAtLeastOnce();
        $percentNbLoggedAtLeastOnce = $nbUsers > 0 ? round($nbLoggedAtLeastOnce / $nbUsers * 100, 2) : 0;
        $uniqueLoginsByYear = $logUserLoginRepository->getUniqueLoginsByYear();
        $uniqueLoginsByQuarter = $logUserLoginRepository->getUniqueLoginsByQuarters();
        $uniqueLoginsByMonth = $logUserLoginRepository->getUniqueLoginsByMonth();
        $uniqueLoginsByWeek = $logUserLoginRepository->getUniqueLoginsByWeek();
        $nbUsersLoggedOnce = $logUserLoginRepository->countUsersLoggedOnce();
        $percentNbUsersLoggedOnce = $nbUsers > 0 ? round($nbUsersLoggedOnce / $nbUsers * 100, 2) : 0;

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
