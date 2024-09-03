<?php

namespace App\Controller\Static;

use App\Controller\FrontController;
use App\Repository\Aid\AidRepository;
use App\Repository\Backer\BackerRepository;
use App\Repository\Log\LogAidViewRepository;
use App\Repository\Log\LogEventRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority: 1)]
class StatsController extends FrontController
{
    #[Route('/stats/', name: 'app_static_stats')]
    public function index(
        AidRepository $aidRepository,
        LogEventRepository $logEventRepository,
        LogAidViewRepository $logAidViewRepository,
        BackerRepository $backerRepository
    ): Response {
        // nb aides
        $nbAids = $aidRepository->countLives();

        // nb notifications
        $nbAlerts = $logEventRepository->countAlertSent();

        // aide vue 7 derniers jours
        $nbAidViews = $logAidViewRepository->countLastWeek();

        // nb porteur
        $nbBackers = $backerRepository->countWithAids();

        // fil arianne
        $this->breadcrumb->add(
            'Statistiques publiques'
        );

        // rendu template  
        return $this->render('static/stats/index.html.twig', [
            'nbAids' => $nbAids,
            'nbAlerts' => $nbAlerts,
            'nbAidViews' => $nbAidViews,
            'nbBackers' => $nbBackers
        ]);
    }
}
