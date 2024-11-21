<?php

namespace App\MessageHandler\Log;

use App\Entity\Log\LogAdminAction;
use App\Entity\User\User;
use App\Message\Log\MsgDeleteOldLogAdmin;
use App\Repository\Log\LogAdminActionRepository;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsMessageHandler()]
class MsgDeleteOldLogAdminHandler
{
    private const BATCH_SIZE = 1000;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private KernelInterface $kernelInterface,
        private NotificationService $notificationService,
        private ParamService $paramService,
        private MessageBusInterface $bus
    ) {
    }

    public function __invoke(MsgDeleteOldLogAdmin $message): void
    {
        try {
            /** @var LogAdminActionRepository $logAdminActionRepository */
            $logAdminActionRepository = $this->managerRegistry->getRepository(LogAdminAction::class);

            $logAdminActions = $logAdminActionRepository->findOldLogs();
            $entityManager = $this->managerRegistry->getManager();
            $batchCount = 0;

            foreach ($logAdminActions as $logAdminAction) {
                $entityManager->remove($logAdminAction);

                if (++$batchCount % self::BATCH_SIZE === 0) {
                    $entityManager->flush();
                }

            }
            $entityManager->flush();
        } catch (ProcessFailedException $exception) {
            // notif admin
            $admin = $this->managerRegistry->getRepository(User::class)
            ->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Erreur MsgDeleteOldLogAdminHandler',
                $exception->getMessage(),
            );
        }
    }
}
