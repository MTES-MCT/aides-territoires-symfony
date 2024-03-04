<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Form\Admin\Filter\DateRangeType;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends DashboardController
{
    #[Route('/admin/statistics/blog/dashboard', name: 'admin_statistics_blog_dashboard')]
    public function blogDashboard(
        AdminContext $adminContext
    )
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
        
        return $this->render('admin/statistics/blog/dashboard.html.twig', [
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);
    }
}