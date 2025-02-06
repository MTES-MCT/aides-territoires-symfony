<?php

namespace App\Controller\Admin\Statistics;

use App\Form\Admin\Filter\DateRangeType;
use App\Repository\Log\LogAidApplicationUrlClickRepository;
use App\Repository\Log\LogAidOriginUrlClickRepository;
use App\Repository\Log\LogAidSearchRepository;
use App\Repository\Log\LogAidViewRepository;
use App\Repository\User\FavoriteAidRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SourceController extends AbstractController
{
    #[Route('/admin/statistics/aids/sources', name: 'admin_statistics_aid_sources')]
    public function index(
        RequestStack $requestStack,
        LogAidSearchRepository $logAidSearchRepository,
        LogAidViewRepository $logAidViewRepository,
        LogAidApplicationUrlClickRepository $logAidApplicationUrlClickRepository,
        LogAidOriginUrlClickRepository $logAidOriginUrlClickRepository,
        FavoriteAidRepository $favoriteAidRepository,
    ): Response {
        // dates par défaut
        $dateMin = new \DateTime('-1 week');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class);
        $formDateRange->handleRequest($requestStack->getCurrentRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        // le nombre de recherche par source
        $nbSearchBySource = $logAidSearchRepository->countBySource([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);

        // le nombre de vues par source
        $nbViewBySource = $logAidViewRepository->countBySource([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);

        // le nombre de clics sur les liens de candidature par source
        $nbApplicationUrlClickBySource = $logAidApplicationUrlClickRepository->countBySource([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);

        // le nombre de clics sur les liens d'origine par source
        $nbOriginUrlClickBySource = $logAidOriginUrlClickRepository->countBySource([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
        ]);

        // sources de favoris
        $nbFavoriteBySource = $favoriteAidRepository->countBySource([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);
        

        return $this->render('admin/statistics/source/index.html.twig', [
            'formDateRange' => $formDateRange,
            'nbSearchBySource' => $nbSearchBySource,
            'nbViewBySource' => $nbViewBySource,
            'nbApplicationUrlClickBySource' => $nbApplicationUrlClickBySource,
            'nbOriginUrlClickBySource' => $nbOriginUrlClickBySource,
            'nbFavoriteBySource' => $nbFavoriteBySource
        ]);
    }
}
