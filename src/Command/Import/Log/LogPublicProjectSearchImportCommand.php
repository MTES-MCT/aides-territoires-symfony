<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:import:log_public_project_search', description: 'Import log public project search')]
class LogPublicProjectSearchImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log public project search';
    protected string $commandTextEnd = '<Import log public project search';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG PUBLIC PROJECT SEARCH
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG PUBLIC PROJECT SEARCH');

        // fichier
        $filePath = $this->findCsvFile('stats_publicprojectsearchevent_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // batch
        $nbByBatch = 3000;
        $nbBatch = ceil($nbToImport / $nbByBatch);
        
        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();

        $sqlBase = "INSERT INTO `log_public_project_search`
        (
        organization_id,
        perimeter_id,
        user_id,
        querystring,
        results_count,
        time_create,
        date_create
        )
        VALUES ";
        $sql = $sqlBase;
        $sqlParams = [];

        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);
        
        ini_set('auto_detect_line_endings',TRUE);
        $rowNumber = 1;
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($raw_string = fgets($handle)) !== false) {
                if ($rowNumber == 1) {
                    $rowNumber++;
                    continue;
                }
                // Parse the raw csv string: "1, a, b, c"
                $data = str_getcsv($raw_string, ';');

                $sql .= "
                (
                    :organization_id".$rowNumber.",
                    :perimeter_id".$rowNumber.",
                    :user_id".$rowNumber.",
                    :querystring".$rowNumber.",
                    :results_count".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber."
                ),";

                $sqlParams['querystring'.$rowNumber] = $this->stringOrNull((string) $data[1]);
                $sqlParams['results_count'.$rowNumber] = (int) $data[2];
                $timeCreate = $this->stringToDateTimeOrNow((string) $data[3]);
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams['organization_id'.$rowNumber] = $this->intOrNull((string) $data[4]);
                $sqlParams['perimeter_id'.$rowNumber] = $this->intOrNull((string) $data[5]);
                $sqlParams['user_id'.$rowNumber] = $this->intOrNull((string) $data[6]);

                if ($rowNumber % $nbByBatch == 0) {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);
                    
                    $sqlParams = [];
                    $sql = $sqlBase;
                    
                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                }

                $rowNumber++;
            }
            fclose($handle);
        }

        try {
            // sauvegarde
            if (count($sqlParams) > 0) {
                $sql = substr($sql, 0, -1);
                $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                $stmt->execute($sqlParams);
            }

        } catch (\Exception $e) {

        }
        // ensures that the progress bar is at 100%
        $io->progressFinish();


        // ==================================================================
        // LOG PUBLIC PROJECT SEARCH / KEYWORDSYNONYM LIST
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG PUBLIC PROJECT SEARCH / KEYWORDSYNONYM LIST');

        // fichier
        $filePath = $this->findCsvFile('stats_publicprojectsearchevent_project_types_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // batch
        $nbByBatch = 3000;
        $nbBatch = ceil($nbToImport / $nbByBatch);
        
        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();

        $sqlBase = "INSERT INTO `log_public_project_search_keyword_synonymlist`
        (
        log_public_project_search_id,
        keyword_synonymlist_id
        )
        VALUES ";
        $sql = $sqlBase;
        $sqlParams = [];

        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);
        
        ini_set('auto_detect_line_endings',TRUE);
        $rowNumber = 1;
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($raw_string = fgets($handle)) !== false) {
                if ($rowNumber == 1) {
                    $rowNumber++;
                    continue;
                }
                // Parse the raw csv string: "1, a, b, c"
                $data = str_getcsv($raw_string, ';');

                $sql .= "
                (
                    :log_public_project_search_id".$rowNumber.",
                    :keyword_synonymlist_id".$rowNumber."
                ),";

                $sqlParams['log_public_project_search_id'.$rowNumber] = (int) $data[1];
                $sqlParams['keyword_synonymlist_id'.$rowNumber] = (int) $data[2];

                if ($rowNumber % $nbByBatch == 0) {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);
                    
                    $sqlParams = [];
                    $sql = $sqlBase;
                    
                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                }

                $rowNumber++;
            }
            fclose($handle);
        }

        try {
            // sauvegarde
            if (count($sqlParams) > 0) {
                $sql = substr($sql, 0, -1);
                $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                $stmt->execute($sqlParams);
            }

        } catch (\Exception $e) {

        }
        // ensures that the progress bar is at 100%
        $io->progressFinish();


        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }
}