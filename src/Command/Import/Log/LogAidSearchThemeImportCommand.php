<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:import:log_aid_search_theme', description: 'Import log aid search theme')]
class LogAidSearchThemeImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log aid search theme';
    protected string $commandTextEnd = '<Import log aid search theme';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG AID SEARCH CATEGORY THEME
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG AID SEARCH CATEGORY THEME');

        // fichier
        $filePath = $this->findCsvFile('stats_aidsearchevent_themes_');
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

        $sqlBase = "INSERT INTO `log_aid_search_category_theme`
        (
        log_aid_search_id,
        category_theme_id
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
                    :log_aid_search_id".$rowNumber.",
                    :category_theme_id".$rowNumber."
                ),";

                $sqlParams['log_aid_search_id'.$rowNumber] = (int) $data[1];
                $sqlParams['category_theme_id'.$rowNumber] = (int) $data[2];

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