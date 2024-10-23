<?php

namespace App\MessageHandler\Perimeter;

use App\Entity\User\User;
use App\Message\Perimeter\MsgPerimeterImport;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsMessageHandler()]
class MsgPerimeterImportHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private KernelInterface $kernelInterface,
        private NotificationService $notificationService,
        private ParamService $paramService
    ) {
    }

    public function __invoke(MsgPerimeterImport $message): void
    {
        $command = ['php', 'bin/console', 'at:cron:perimeter:perimeter_import'];

        $process = new Process($command);
        $process->setWorkingDirectory($this->kernelInterface->getProjectDir());

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            // notif admin
            $admin = $this->managerRegistry->getRepository(User::class)
            ->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Envoi MsgPerimeterImport',
                $exception->getMessage(),
            );
        }
    }
}
