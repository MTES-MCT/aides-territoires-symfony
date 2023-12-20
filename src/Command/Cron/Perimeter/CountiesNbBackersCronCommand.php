<?php

namespace App\Command\Cron\Perimeter;

use App\Entity\Backer\Backer;
use App\Entity\Perimeter\Perimeter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'at:cron:perimeter:counties_nb_backers', description: 'Calcul nb backers / county')]
class CountiesNbBackersCronCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Cron';
    protected string $commandTextEnd = '>Cron';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry
    )
    {
        ini_set('max_execution_time', 60*60*60);
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

        // charge les départements
        $counties = $this->managerRegistry->getRepository(Perimeter::class)->findCounties();

        // pour chaque département, compte le nombre de backer
        foreach ($counties as $county) {
            $county->setBackersCount($this->managerRegistry->getRepository(Backer::class)->countBackerWithAidInCounty(['id' => $county->getId()]));
            $this->managerRegistry->getManager()->persist($county);
        }

        // sauvegarde
        $this->managerRegistry->getManager()->flush();

        // success
        $io->success('Calcul nb backers / county ok');
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }
}