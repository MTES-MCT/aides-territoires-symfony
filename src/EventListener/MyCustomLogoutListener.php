<?php

namespace App\EventListener;

use App\Entity\Log\LogAdminAction;
use App\Entity\Log\LogUserLogin;
use App\Entity\User\User;
use App\Service\Security\ProConnectService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class MyCustomLogoutListener
{
    public function __construct(
        private UserService $userService,
        private ProConnectService $proConnectService,
        private Security $security,
        private ManagerRegistry $managerRegistry
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

            // on regarde si on est sur le firewall admin
            if (
                $this->security->getFirewallConfig($logoutEvent->getRequest())->getName()
                === LogAdminAction::FIREWALL_ADMIN_NAME
            ) {
                /** @var User $user */
                $user = $logoutEvent->getToken()->getUser();

                // on log la déconnexion de l'admin
                $logAdminAction = new LogAdminAction();
                $logAdminAction->setObjectClass(User::class);
                $logAdminAction->setObjectId($user->getId());
                $logAdminAction->setObjectRepr($user->getFirstname());
                $logAdminAction->setActionFlag(LogAdminAction::ACTION_FLAG_LOGOUT);
                $logAdminAction->setAdmin($user);

                $this->managerRegistry->getManager()->persist($logAdminAction);
                $this->managerRegistry->getManager()->flush();
            }

            // On déconnecte sur ProConnect si besoin
            $proConnectLogoutUrl = $this->proConnectService->getLogoutUrl();
            if ($proConnectLogoutUrl) {
                // on envoi sur la déconnexion ProConnect qui nous renverra ensuite sur la déconnexion Symfony
                $logoutEvent->setResponse(new RedirectResponse($proConnectLogoutUrl));
            }
        }
    }
}
