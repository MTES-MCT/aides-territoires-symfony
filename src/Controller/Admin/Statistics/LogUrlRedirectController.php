<?php

namespace App\Controller\Admin\Statistics;

use App\Form\Admin\Filter\DateRangeType;
use App\Repository\Log\LogUrlRedirectRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogUrlRedirectController extends AbstractController
{
    #[Route('/admin/statistics/log/url-redirect', name: 'admin_statistics_log_url_redirect')]
    public function index(
        AdminContext $adminContext,
        LogUrlRedirectRepository $logUrlRedirectRepository
    ): Response {
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

        // les logs de redictions, groupées par url
        $logUrlRedirects = $logUrlRedirectRepository->findGroupByUrl([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);

        // rendu template
        return $this->render('admin/statistics/log/url_redirect.html.twig', [
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'logUrlRedirects' => $logUrlRedirects,
        ]);
    }
}
