<?php

namespace App\Controller\Static;

use App\Controller\FrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority:1)]
class LegalNoticeController extends FrontController
{
    #[Route('/mentions-légales/', name: 'app_static_legal_notice')]
    public function index(): Response
    {
        // fil arianne
        $this->breadcrumb->add(
            'Mentions légales'
        );

        // rendu template        
        return $this->render('static/legal_notice/index.html.twig', [
        ]);
    }
}
