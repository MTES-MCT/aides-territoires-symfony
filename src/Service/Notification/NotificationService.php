<?php

namespace App\Service\Notification;

use App\Entity\User\Notification;
use App\Entity\User\User;
use Doctrine\Persistence\ManagerRegistry;

class NotificationService
{
    public function __construct(
        protected ManagerRegistry $managerRegistry
    )
    {
    }

    public function addNotification(User $user, string $name, string $description): void
    {
        try {
            $notification = new Notification();
            $notification->setName($name);
            $notification->setDescription($description);
            $notification->setUser($user);
            $this->managerRegistry->getManager()->persist($notification);
    
            $user->setNotificationCounter($user->getNotificationCounter() + 1);
            $this->managerRegistry->getManager()->persist($user);
    
            $this->managerRegistry->getManager()->flush();
        } catch (\Exception $e) {
        }
    }
}