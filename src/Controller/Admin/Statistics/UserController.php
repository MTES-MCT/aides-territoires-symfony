<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Aid\AidProject;
use App\Entity\Log\LogUserLogin;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class UserController extends DashboardController
{
    // public function __construct(
    //     protected ManagerRegistry $managerRegistry,
    //     protected ChartBuilderInterface $chartBuilderInterface,
    // )
    // {   
    // }

    #[Route('/admin/statistics/user/dashboard', name: 'admin_statistics_user_dashboard')]
    public function communeDashboard(
    ): Response
    {
        // les fréquences de connexions
        $loginFrequencies = $this->managerRegistry->getRepository(LogUserLogin::class)->getLoginFrequencies();
        dump($loginFrequencies);

        // retour template
        return $this->render('admin/statistics/user/dashboard.html.twig', [

        ]);
    }

}