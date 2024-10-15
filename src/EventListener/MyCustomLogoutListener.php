<?php

namespace App\EventListener;

use App\Entity\Log\LogUserLogin;
use App\Entity\User\User;
use App\Service\Security\ProConnectService;
use App\Service\User\UserService;
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
            // On log la déconnexion de l'utilisateur
            $this->userService->setLogUser(
                [
                    'user'          => $logoutEvent->getToken()->getUser(),
                    'action'        => LogUserLogin::ACTION_LOGOUT,
                ]
            );

            // On déconnecte sur ProConnect si besoin
            $this->proConnectService->logout();
        }
    }
}
