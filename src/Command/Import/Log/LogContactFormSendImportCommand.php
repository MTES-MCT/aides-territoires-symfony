<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:import:log_contact_form_send', description: 'Import log contact form send')]
class LogContactFormSendImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log contact form send';
    protected string $commandTextEnd = '<Import log contact form send';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG CONTACT FORM SEND
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG CONTACT FORM SEND');

        // fichier
        $filePath = $this->findCsvFile('stats_contactformsendevent_');
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

        $sqlBase = "INSERT INTO `log_contact_form_send`
        (
        subject,
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
                    :subject".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber."
                ),";

                $sqlParams['subject'.$rowNumber] = $this->stringOrNull((string) $data[1]);
                $timeCreate = $this->stringToDateTimeOrNow((string) $data[2]);
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');

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