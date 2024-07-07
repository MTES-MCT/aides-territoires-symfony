<?php

namespace App\MessageHandler\Export;

use App\Entity\User\User;
use App\Message\Export\MsgSpreadsheetToExport;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsMessageHandler()]
class MsgSpreadsheetToExportHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private KernelInterface $kernelInterface,
        private NotificationService $notificationService,
        private ParamService $paramService
    ) {
    }
    public function __invoke(MsgSpreadsheetToExport $message): void
    {
        $command = ['php', 'bin/console', 'at:cron:export:spreadsheet_export'];

        $process = new Process($command);
        $process->setWorkingDirectory($this->kernelInterface->getProjectDir()); // Assurez-vous de définir le bon répertoire de travail

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            // notif admin
            $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Envoi MsgSpreadsheetToExport',
                $exception->getMessage(),
            );
        }
    }
}
