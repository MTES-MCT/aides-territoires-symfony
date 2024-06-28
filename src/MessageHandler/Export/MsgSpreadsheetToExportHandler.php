<?php

namespace App\MessageHandler\Export;

use App\Entity\Backer\Backer;
use App\Entity\Perimeter\Perimeter;
use App\Kernel;
use App\Message\Perimeter\CountyCountBacker;
use App\Message\Export\MsgSpreadsheetToExport;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsMessageHandler()]
class MsgSpreadsheetToExportHandler
{
    public function __construct(
        private KernelInterface $kernelInterface,
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
            // Gérer l'erreur si la commande échoue
            dd($exception->getMessage());
        }
    }
}
