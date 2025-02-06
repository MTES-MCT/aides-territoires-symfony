<?php

namespace App\Controller\Admin\Statistics;

use App\Form\Admin\Filter\DateRangeType;
use App\Repository\User\FavoriteAidRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FavoriteAidController extends AbstractController
{
    #[Route('/admin/statistics/aids/favorites', name: 'admin_statistics_aid_favorites')]
    public function index(
        RequestStack $requestStack,
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

        // le top 10
        $topFavoriteAids = $favoriteAidRepository->countTopAids([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        // nb par jour
        $nbFavoriteAidsByDay = $favoriteAidRepository->countByDay([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        // nb total
        $totalFavoriteAids = $favoriteAidRepository->countTotal([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        // rendu template
        return $this->render('admin/statistics/favorite_aid/index.html.twig', [
            'formDateRange' => $formDateRange,
            'topFavoriteAids' => $topFavoriteAids,
            'nbFavoriteAidsByDay' => $nbFavoriteAidsByDay,
            'totalFavoriteAids' => $totalFavoriteAids,
        ]);
    }
}
