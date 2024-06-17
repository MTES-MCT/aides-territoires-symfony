<?php

namespace App\Command\Script;

use App\Entity\Log\LogAidSearch;
use App\Entity\Perimeter\Perimeter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:script:log_aid_search_clean', description: 'Clean log aid search')]
class LogAidSearchCleanCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Clean log aid search';
    protected string $commandTextEnd = '>Clean log aid search';

    private int $offset = 0;
    private int $maxResults = 500000;
    

    public function __construct(
        protected ManagerRegistry $managerRegistry,
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

        $timeStart = microtime(true);
        $nbByBatch = 20000;
        $pattern = '/(searchPerimeter|perimeter)=([0-9]+)/';
        $perimetersById = [];
        $logAidSearchRepository = $this->managerRegistry->getRepository(LogAidSearch::class);
        $perimeterRepository = $this->managerRegistry->getRepository(Perimeter::class);
        // recupere les x logs avec perimeter_id = null
        $logAidSearchs = $logAidSearchRepository->findBy(
            [
                'perimeter' => null
            ],
            ['id' => 'ASC'],
            $this->maxResults,
            $this->offset
        );

        $nbCurrent = 0;
        /** @var LogAidSearch $logAidSearch */
        foreach ($logAidSearchs as $logAidSearch) {
            if (!$logAidSearch->getQuerystring()) {
                $nbCurrent++;
                continue;
            }
            if (preg_match($pattern, $logAidSearch->getQuerystring(), $matches)) {
                $searchPerimeterValue = $matches[2] ?? null;
                
                if ($searchPerimeterValue) {
                    if (!isset($perimetersById[$searchPerimeterValue])) {
                        $perimeter = $perimeterRepository->find($searchPerimeterValue);
                        if ($perimeter) {
                            $perimetersById[$searchPerimeterValue] = $perimeter;
                        }
                    }

                    if (isset($perimetersById[$searchPerimeterValue])) {
                        $logAidSearch->setPerimeter($perimetersById[$searchPerimeterValue]);
                        $this->managerRegistry->getManager()->persist($logAidSearch);
                    }
                }
            }

            $nbCurrent++;

            if ($nbCurrent >= $nbByBatch) {
                $this->managerRegistry->getManager()->flush();
                $nbCurrent = 0;
            }
        }

        $this->managerRegistry->getManager()->flush();

        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $io->success('Fin des opérations : '.gmdate("H:i:s", $timeEnd).' ('.gmdate("H:i:s", $time).')');
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');

        return Command::SUCCESS;
    }
}