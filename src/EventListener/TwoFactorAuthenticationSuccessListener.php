<?php

namespace App\EventListener;

use App\Entity\Log\LogAdminAction;
use App\Entity\User\User;
use Doctrine\Persistence\ManagerRegistry;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TwoFactorAuthenticationListener
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function onTwoFactorAuthenticationSuccess(TwoFactorAuthenticationEvent $event): void
    {
        /** @var User $user */
        $user = $event->getToken()->getUser();

        if ($this->authorizationChecker->isGranted(User::ROLE_ADMIN, $user)) {
            $logAdminAction = new LogAdminAction();
            $logAdminAction->setObjectClass(User::class);
            $logAdminAction->setObjectId($user->getId());
            $logAdminAction->setObjectRepr($user->getFirstname());
            $logAdminAction->setActionFlag(LogAdminAction::ACTION_FLAG_LOGIN);
            $logAdminAction->setAdmin($user);

            $this->managerRegistry->getManager()->persist($logAdminAction);
            $this->managerRegistry->getManager()->flush();
        }
    }
}
