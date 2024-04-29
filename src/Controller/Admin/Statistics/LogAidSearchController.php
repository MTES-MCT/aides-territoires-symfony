<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Log\LogAidSearch;
use App\Form\Admin\Filter\DateRangeType;
use App\Repository\Log\LogAidSearchRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Routing\Attribute\Route;

class LogAidSearchController extends DashboardController
{
    #[Route('/admin/statistics/log/aid-search', name: 'admin_statistics_log_aid_search')]
    public function blogDashboard(
        AdminContext $adminContext,
        LogAidSearchRepository $logAidSearchRepository,
        ProjectReferenceRepository $projectReferenceRepository
    )
    {
        // dates par défaut
        $dateMin = new \DateTime('-1 week');
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
            'resultsCountMax' => 10,
            'orderBy' => [
                'sort' => 'l.timeCreate',
                'order' => 'DESC'
            ]
        ]);

        $queriesByLogId = [];
        /** @var LogAidSearch $logAidSearch */
        foreach ($logAidSearchs as $logAidSearch) {
            $queriesByLogId[$logAidSearch->getId()] = explode('&', $logAidSearch->getQuerystring());
        }

        // regarde si il y a un projet référent correspondant à la recherche
        $projectReferences = $projectReferenceRepository->findAll();
        $projectReferencesByLogId = [];
        foreach ($logAidSearchs as $logAidSearch) {
            $projectReferencesByLogId[$logAidSearch->getId()] = null;
            foreach ($projectReferences as $projectReference) {
                if ($projectReference->getName() == $logAidSearch->getSearch()) {
                    $projectReferencesByLogId[$logAidSearch->getId()] = $projectReference;
                    break;
                }
            }
        }

        return $this->render('admin/statistics/log/aid-search.html.twig', [
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'logAidSearchs' => $logAidSearchs,
            'queriesByLogId' => $queriesByLogId,
            'projectReferencesByLogId' => $projectReferencesByLogId
        ]);
    }
}