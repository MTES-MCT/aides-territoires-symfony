<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Alert\Alert;
use App\Repository\Alert\AlertRepository;
use App\Service\Email\EmailService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority:5)]
class AlertController extends FrontController
{
    #[Route('/comptes/alertes/vos-alertes/', name: 'app_user_alert')]
    public function index(
        UserService $userService,
        AlertRepository $alertRepository
    )
    {
        // user actuel
        $user = $userService->getUserLogged();

        // les alertes
        $alerts = $alertRepository->findBy(
            [
                'email' => $user->getEmail()
            ]
            );
        // rendu template
        return $this->render('user/alert/index.html.twig', [
            'alertList' => true,
            'alerts' => $alerts
        ]);
    }

    #[Route('/comptes/alertes/vos-alertes/{id}/suppression', name: 'app_user_alert_delete')]
    public function delete(
        $id,
        UserService $userService,
        AlertRepository $alertRepository,
        ManagerRegistry $managerRegistry
    )
    {
        // user actuel
        $user = $userService->getUserLogged();

        // alerte a supprimer
        $alert = $alertRepository->findOneBy(
            [
                'id' => $id,
                'email' => $user->getEmail()
            ]
        );   
        if (!$alert instanceof Alert) {
            throw new NotFoundHttpException('Alerte introuvable');
        }
        
        try {
            $managerRegistry->getManager()->remove($alert);
            $managerRegistry->getManager()->flush();

            $this->tAddFlash(
                FrontController::FLASH_SUCCESS,
                'Votre alerte a bien été supprimée.'
            );
        } catch (\Exception $e) {
            $this->tAddFlash(
                FrontController::FLASH_ERROR,
                'Une erreur s’est produite lors de la suppression de votre alerte'
            );
        }

        return $this->redirectToRoute('app_user_alert');
    }

    #[Route('/comptes/inscription-newsletter/', name: 'app_user_alert_newsletter_subscribe')]
    public function newsletterSubscribe(
        UserService $userService,
        EmailService $emailService
    )
    {
        // user actuel
        $user = $userService->getUserLogged();

        // inscription à la newsletter
        if ($emailService->subscribeUser($user)) {
            $this->tAddFlash(
                FrontController::FLASH_SUCCESS,
                'Votre demande d’inscription à la newsletter a bien été prise en compte.<br />
                <strong>Afin de finaliser votre inscription il vous reste à cliquer sur le lien
                de confirmation présent dans l’e-mail que vous allez recevoir.</strong>'
            );
        } else {
            // erreur Service, on notifie l'utilisateur
            $this->tAddFlash(
                FrontController::FLASH_ERROR,
                'Une erreur s\'est produite lors de votre inscription à la newsletter'
            );
        }

        
        return $this->redirectToRoute('app_user_alert');
    }


    #[Route('/comptes/desinscription-newsletter/', name: 'app_user_alert_newsletter_unsubscribe')]
    public function newsletterUnsubscribe(
        UserService $userService,
        EmailService $emailService,
        ManagerRegistry $managerRegistry
    )
    {
        // user actuel
        $user = $userService->getUserLogged();

        // désinscrit l'utilisateur
        if ($emailService->unsubscribeUser($user)) {
            // retour Service ok, on met à jour notre base
            $user->setMlConsent(false);
            $managerRegistry->getManager()->persist($user);
            $managerRegistry->getManager()->flush();

            $this->tAddFlash(
                FrontController::FLASH_SUCCESS,
                'Vous avez été désabonné de la newsletter'
            );
        } else {
            // erreur Service, on notifie l'utilisateur
            $this->tAddFlash(
                FrontController::FLASH_ERROR,
                'Nous n\'avons pas réussi à vous désabonner de la newsletter'
            );
        }

        // redirection
        return $this->redirectToRoute('app_user_alert');
    }
}