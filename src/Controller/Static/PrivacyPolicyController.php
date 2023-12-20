<?php

namespace App\Controller\Static;

use App\Controller\FrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrivacyPolicyController extends FrontController
{
    #[Route('/politique-de-confidentialité/', name: 'app_static_privacy_policy')]
    public function index(): Response
    {
        // fil arianne
        $this->breadcrumb->add(
            'Politique de confidentialité'
        );

        // rendu template  
        return $this->render('static/privacy_policy/index.html.twig', [
        ]);
    }
}
