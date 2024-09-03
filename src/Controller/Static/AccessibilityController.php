<?php

namespace App\Controller\Static;

use App\Controller\FrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority: 1)]
class AccessibilityController extends FrontController
{
    #[Route('/accessibilité/', name: 'app_static_accessibility')]
    public function index(): Response
    {
        // fil arianne
        $this->breadcrumb->add(
            'Déclaration d’accessibilité'
        );

        // rendu template
        return $this->render('static/accessibility/index.html.twig', []);
    }
}
