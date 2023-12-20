<?php

namespace App\Controller\Static;

use App\Controller\FrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority:1)]
class DataController extends FrontController
{
    #[Route('/data/', name: 'app_static_data')]
    public function index(): Response
    {
        // fil arianne
        $this->breadcrumb->add(
            'API et réutilisations des données'
        );

        // rendu template
        return $this->render('static/data/index.html.twig', []);
    }
}
