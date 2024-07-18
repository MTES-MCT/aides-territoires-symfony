<?php

namespace App\MessageHandler\Site;

use App\Entity\User\User;
use App\Message\Site\MsgDebugMemory;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class MsgDebugMemoryHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ParamService $paramService,
        private NotificationService $notificationService
    ) {
    }
    public function __invoke(MsgDebugMemory $message): void
    {
        $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
        $this->notificationService->addNotification(
            $admin,
            'Memoire disponible dans worker',
            'config : '.ini_get('memory_limit'). ' currentUsage : '.round(memory_get_usage() / 1024 / 1024) . ' MB'. ' peakUsage : '.round(memory_get_peak_usage() / 1024 / 1024) . ' MB',
        );
    }
}
