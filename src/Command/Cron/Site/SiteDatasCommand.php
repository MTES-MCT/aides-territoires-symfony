<?php

namespace App\Command\Cron\Site;

use App\Entity\Reference\ProjectReference;
use App\Message\Reference\ProjectReferenceCountAids;
use App\Repository\Reference\ProjectReferenceRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'at:cron:site:datas', description: 'Cron Datas du site')]
class SiteDatasCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Cron Datas du site';
    protected string $commandTextEnd = '>Cron Datas du site';

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private MessageBusInterface $bus
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try  {
            // tache
            $this->cronTask($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function cronTask($input, $output)
    {
        $timeStart = microtime(true);

        $io = new SymfonyStyle($input, $output);
        
        // Les projets référents
        /** @var ProjectReferenceRepository $projectReferenceRepository */
        $projectReferenceRepository = $this->managerRegistry->getRepository(ProjectReference::class);

        $projectReferences = $projectReferenceRepository->findAll();

        foreach ($projectReferences as $projectReference) {
            // on envoi le projet pour comptage
            $this->bus->dispatch(new ProjectReferenceCountAids($projectReference->getId()));
        }


        // le temps passé
        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        // success
        $io->success('Temps écoulé : '.gmdate("H:i:s", $timeEnd).' ('.gmdate("H:i:s", intval($time)).')');
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }
}
