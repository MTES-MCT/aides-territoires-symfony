<?php

namespace App\Controller\Static;

use App\Controller\FrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority: 1)]
class TermsController extends FrontController
{
    #[Route('/conditions-generales-dutilisation/', name: 'app_static_terms')]
    public function index(): Response
    {
        // fil arianne
        $this->breadcrumb->add(
            'Conditions générales d\'utilisation'
        );

        // rendu template
        return $this->render('static/terms/index.html.twig', []);
    }
}
