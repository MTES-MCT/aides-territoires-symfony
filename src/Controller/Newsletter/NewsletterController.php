<?php

namespace App\Controller\Newsletter;

use App\Controller\FrontController;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewsletterController extends FrontController
{
    #[Route('/inscription-newsletter-a-confirmer/', name: 'app_newsletter_register_to_confirm')]
    public function registerToConfirm() : Response {
        // fil arianne
        $this->breadcrumb->add(
            'Merci de confirmer votre inscription'
        );

        // rendu template
        return $this->render('newsletter/register-to-confirm.html.twig', [

        ]);
    }

    #[Route('/inscription-newsletter-succes/', name: 'app_newsletter_register_success')]
    public function registerSuccess(
        UserService $userService,
        ManagerRegistry $managerRegistry
    ) : Response {
        // fil arianne
        $this->breadcrumb->add(
            'Vous êtes abonné'
        );

        // si on a le user, on met optin
        $user = $userService->getUserLogged();
        if ($user) {
            $user->setMlConsent(true);
            $managerRegistry->getManager()->persist($user);
            $managerRegistry->getManager()->flush();
        }
        // rendu template
        return $this->render('newsletter/register-success.html.twig', [

        ]);
    }
}