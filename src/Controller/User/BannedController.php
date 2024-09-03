<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(priority: 5)]
class BannedController extends AbstractController
{
    #[Route('/utilisateur-banni/', name: 'app_user_banned')]
    public function index(): Response
    {
        return $this->render('user/banned/index.html.twig', []);
    }
}
