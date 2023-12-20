<?php

namespace App\Controller\Static;

use App\Controller\FrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority:1)]
class SitemapController extends FrontController
{
    #[Route('/plan-du-site/', name: 'app_static_sitemap')]
    public function index(): Response
    {
        // fil arianne
        $this->breadcrumb->add(
            'Plan du site'
        );

        // rendu template  
        return $this->render('static/sitemap/index.html.twig', [
        ]);
    }
}
