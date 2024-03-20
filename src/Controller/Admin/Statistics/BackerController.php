<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Blog\BlogPost;
use App\Form\Admin\Filter\DateRangeType;
use App\Repository\Backer\BackerRepository;
use App\Repository\Log\LogBlogPostViewRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class BackerController extends DashboardController
{
    #[Route('/admin/statistics/backer/dashboard', name: 'admin_statistics_backer_dashboard')]
    public function backerDashboard(
        AdminContext $adminContext,
        BackerRepository $backerRepository,
        FormFactoryInterface $formFactoryInterface,
        ChartBuilderInterface $chartBuilderInterface
    )
    {
        // tous les porteurs
        $backers = $backerRepository->findBy(
            [],
            ['nbAidsLive' => 'DESC']
        );

        // grapgique
        $chartBackerAids = $chartBuilderInterface->createChart(Chart::TYPE_PIE);

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
            $labels[] = $backer->getName() . ' ('.$percentage.'%)';
            $datas[] = $backer->getNbAidsLive();
            $colors[] = 'rgb('.rand(0, 255).', '.rand(0, 255).', '.rand(0, 255).')';
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
        ]);
    }
}