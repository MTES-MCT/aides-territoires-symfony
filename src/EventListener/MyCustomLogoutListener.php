<?php

namespace App\EventListener;

use App\Entity\Log\LogUserLogin;
use App\Entity\User\User;
use App\Service\Security\ProConnectService;
use App\Service\User\UserService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class MyCustomLogoutListener
{
    protected $entityManager;
    protected $tools;

    public function __construct(
        private UserService $userService,
        private ProConnectService $proConnectService
    ) {
    }
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $logoutEvent): void
    {
        if ($logoutEvent->getToken() && $logoutEvent->getToken()->getUser() instanceof User) {
            // On déconnecte sur ProConnect si besoin
            $proConnectLogoutUrl = $this->proConnectService->getLogoutUrl();
            if ($proConnectLogoutUrl) {
                // on envoi sur la déconnexion ProConnect qui nous renverra ensuite sur la déconnexion Symfony
                $logoutEvent->setResponse(new RedirectResponse($proConnectLogoutUrl));
                return;
            }
            
            // On log la déconnexion de l'utilisateur
            $this->userService->setLogUser(
                [
                    'user'          => $logoutEvent->getToken()->getUser(),
                    'action'        => LogUserLogin::ACTION_LOGOUT,
                ]
            );
        }
    }
}
