<?php

namespace App\Command\Cron\Backer;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'at:cron:backer:count_aids', description: 'Calcul nb aids / backer')]
class BackerCountAidsCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Calcul nb aids / backer';
    protected string $commandTextEnd = '>Calcul nb aids / backer';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function configure() : void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try  {
            if (!$this->kernelInterface->getEnvironment() != 'prod') {
                $io->info('Uniquement en prod');
                return Command::FAILURE;
            }
            // generate menu
            $this->cronTask($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function cronTask($input, $output)
    {
        $io = new SymfonyStyle($input, $output);
        $timeStart = microtime(true);

        // charge les porteurs d'aides
        $backers = $this->managerRegistry->getRepository(Backer::class)->findAll();

        // pour chaque backer, compte le nombre d'aides
        /** @var Backer $backer */
        foreach ($backers as $backer) {
            $backer->setNbAids($this->managerRegistry->getRepository(Aid::class)->countCustom(['backer' => $backer]));
            $backer->setNbAidsLive($this->managerRegistry->getRepository(Aid::class)->countCustom(['backer' => $backer, 'showInSearch' => true]));
            $this->managerRegistry->getManager()->persist($backer);
        }

        // sauvegarde
        $this->managerRegistry->getManager()->flush();

        // fin
        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;
        $io->success('Temps écoulé : '.gmdate("H:i:s", $timeEnd).' ('.gmdate("H:i:s", intval($time)).')');
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }
}