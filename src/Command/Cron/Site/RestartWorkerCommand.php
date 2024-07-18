<?php

namespace App\Command\Cron\Site;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'at:cron:site:restart_worker', description: 'Redemarrage worker')]
class RestartWorkerCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Redemarrage worker';
    protected string $commandTextEnd = '>Redemarrage worker';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try  {
            // Arrêter les workers
            $stopWorkersProcess = new Process(['bin/console', 'messenger:stop-workers']);
            $stopWorkersProcess->run();

            // Vérifier si le processus s'est bien exécuté
            if (!$stopWorkersProcess->isSuccessful()) {
                throw new ProcessFailedException($stopWorkersProcess);
            }

            $io->success('Workers arrêtés avec succès.');

            // Attendre un peu avant de redémarrer
            sleep(5);

            // Redémarrer le worker
            $startWorkerProcess = new Process(['php', 'bin/console', 'messenger:consume', 'async']);
            $startWorkerProcess->start();

            $io->success('Worker redémarré avec succès.');
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }
}
