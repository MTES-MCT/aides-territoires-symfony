<?php

namespace App\MessageHandler\Aid;

use App\Entity\User\User;
use App\Message\Aid\MsgImportFlux;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsMessageHandler()]
class MsgImportFluxHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private KernelInterface $kernelInterface,
        private NotificationService $notificationService,
        private ParamService $paramService
    ) {
    }

    public function __invoke(MsgImportFlux $message): void
    {
        $command = ['php', 'bin/console',  $message->getCommand()];

        try {
            $process = new Process($command);
            $process->setWorkingDirectory($this->kernelInterface->getProjectDir());
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            // notif admin
            $admin = $this->managerRegistry->getRepository(User::class)
            ->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                $message->getCommand(),
                $exception->getMessage(),
            );
        }
    }
}
