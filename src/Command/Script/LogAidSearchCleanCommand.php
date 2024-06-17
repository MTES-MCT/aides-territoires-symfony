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
    private int $maxResults = 250000;

    public function __construct(
        protected ManagerRegistry $managerRegistry,
    )
    {
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

        $perimeterRepository = $this->managerRegistry->getRepository(Perimeter::class);
        // recupere les x logs avec perimeter_id = null
        $sql = "SELECT *
                FROM log_aid_search
                WHERE perimeter_id IS NULL
                AND (querystring like '%perimeter%' or querystring like '%searchPerimeter%')
                LIMIT 0, 300000
                ";
        $stmt = $this->managerRegistry->getConnection()->prepare($sql);
        
        try {
            $result = $stmt->executeQuery();
            $logAidSearchs = $result->fetchAllAssociative();
        } finally {
            $result->free();
        }
        
        $nbCurrent = 0;
        $sqlUpdates = [];
        /** @var LogAidSearch $logAidSearch */
        foreach ($logAidSearchs as $key => $logAidSearch) {
            if (preg_match($pattern, $logAidSearch['querystring'], $matches)) {
                $searchPerimeterValue = $matches[2] ?? null;

                if ($searchPerimeterValue) {
                    if (!isset($perimetersById[$searchPerimeterValue])) {
                        $perimeter = $perimeterRepository->find($searchPerimeterValue);
                        if ($perimeter) {
                            $perimetersById[$searchPerimeterValue] = $perimeter;
                        }
                    }

                    if (isset($perimetersById[$searchPerimeterValue])) {
                        $sqlUpdates[] = "UPDATE log_aid_search SET perimeter_id = ".$perimetersById[$searchPerimeterValue]->getId()." WHERE id = ".$logAidSearch['id'];
                    }
                }
            }

            $nbCurrent++;
            unset($logAidSearch[$key]);

            if ($nbCurrent >= $nbByBatch) {

                if (!empty($sqlUpdates)) {
                    $stmt = $this->managerRegistry->getConnection()->prepare(implode(';', $sqlUpdates));
                    
                    try {
                        $result = $stmt->executeQuery();
                    } finally {
                        $result->free();
                    }
                }
                $nbCurrent = 0;
                $sqlUpdates = [];
            }
        }

        if (!empty($sqlUpdates)) {
            $stmt = $this->managerRegistry->getConnection()->prepare(implode(';', $sqlUpdates));
            
            try {
                $result = $stmt->executeQuery();
            } finally {
                $result->free();
            }
        }

        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $io->success('Fin des opérations : '.gmdate("H:i:s", $timeEnd).' ('.gmdate("H:i:s", $time).')');
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');

        return Command::SUCCESS;
    }
}
