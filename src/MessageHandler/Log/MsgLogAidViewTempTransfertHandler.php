<?php

namespace App\MessageHandler\Log;

use App\Entity\User\User;
use App\Message\Log\MsgLogAidViewTempTransfert;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsMessageHandler()]
class MsgLogAidViewTempTransfertHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private KernelInterface $kernelInterface,
        private NotificationService $notificationService,
        private ParamService $paramService
    ) {
    }

    public function __invoke(MsgLogAidViewTempTransfert $message): void
    {
        try {
            // Insérer les logs de log_temp dans log_aid_view
            $sqlInsert = '
            INSERT INTO log_aid_view (aid_id, organization_id, user_id, querystring, source, time_create, date_create)
            SELECT aid_id, organization_id, user_id, querystring, source, time_create, date_create
            FROM log_temp
            WHERE date_create = CURDATE() - INTERVAL 1 DAY;
            ';
            $this->managerRegistry->getConnection()->executeStatement($sqlInsert);

            // Nettoyer log_temp
            $sqlDelete = '
                DELETE FROM log_temp WHERE date_create = CURDATE() - INTERVAL 1 DAY;
            ';
            $this->managerRegistry->getConnection()->executeStatement($sqlDelete);

        } catch (ProcessFailedException $exception) {
            // notif admin
            $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Erreur MsgLogAidViewTempTransfert',
                $exception->getMessage(),
            );
        }
    }
}
