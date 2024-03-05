<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Blog\BlogPost;
use App\Form\Admin\Filter\DateRangeType;
use App\Repository\Log\LogAidSearchRepository;
use App\Repository\Log\LogBlogPostViewRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class LogAidSearchController extends DashboardController
{
    #[Route('/admin/statistics/log/aid-search', name: 'admin_statistics_log_aid_search')]
    public function blogDashboard(
        AdminContext $adminContext,
        LogAidSearchRepository $logAidSearchRepository,
        FormFactoryInterface $formFactoryInterface
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

        // les recherches qui donnent peu de résultats
        $logAidSearchs = $logAidSearchRepository->findKeywordSearchWithFewResults([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'hasSearch' => true,
            'resultsCountMax' => 15,
            'orderBy' => [
                'sort' => 'l.timeCreate',
                'order' => 'DESC'
            ]
        ]);

        return $this->render('admin/statistics/log/aid-search.html.twig', [
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'logAidSearchs' => $logAidSearchs,
        ]);
    }
}