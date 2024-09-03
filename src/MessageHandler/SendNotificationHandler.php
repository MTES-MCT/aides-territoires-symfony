<?php

namespace App\MessageHandler;

use App\Entity\User\User;
use App\Message\SendNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class SendNotificationHandler
{
    public function __construct(
        private NotificationService $notificationService,
        private ManagerRegistry $managerRegistry,
        private ParamService $paramService
    ) {}

    public function __invoke(SendNotification $message)
    {
        $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
        $this->notificationService->addNotification(
            $admin,
            $message->getTitle(),
            $message->getMessage()
        );
    }
}
