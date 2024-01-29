<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidProject;
use App\Entity\Backer\Backer;
use App\Entity\Organization\Organization;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends DashboardController
{
    #[Route('/admin/statistics/dashboard', name: 'admin_statistics_dashboard')]
    public function dashboard()
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
        $nbEcpi = $this->managerRegistry->getRepository(Organization::class)->countEcpi([]);
        // $chart = $this->cre
        dump($nbCommune);
        return $this->render('admin/statistics/dashboard.html.twig', [
            'nbUsers' => $nbUsers,
            'nbOrganizations' => $nbOrganizations,
            'nbInterco' => $nbInterco,
            'nbProjects' => $nbProjects,
            'nbAidProjects' => $nbAidProjects,
            'nbAidsLive' => $nbAidsLive,
            'nbBackers' => $nbBackers,
            'nbSearchPages' => $nbSearchPages
        ]);
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

        $chart = $this->chartBuilderInterface->createChart(Chart::TYPE_DOUGHNUT);

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