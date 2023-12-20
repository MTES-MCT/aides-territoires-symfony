<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:log_event', description: 'Import log event')]
class LogEventImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log event';
    protected string $commandTextEnd = '<Import log event';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG ADMIN ACTION
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG EVENT');

        // fichier
        $filePath = $this->findCsvFile('stats_event_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        // ouverture du fichier
        $options = new Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';
        $reader = new Reader($options);
        $reader->open($filePath);

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

        $sqlBase = "INSERT INTO `log_event`
        (
        `id`,
        `value`,
        time_create,
        date_create,
        category,
        `event`,
        meta,
        `source`
        )
        VALUES ";
        $sql = $sqlBase;
        $sqlParams = [];
        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);

        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                $sql .= "
                (
                    :id".$rowNumber.",
                    :value".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :category".$rowNumber.",
                    :event".$rowNumber.",
                    :meta".$rowNumber.",
                    :source".$rowNumber."
                ),";

                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams['value'.$rowNumber] = $this->intOrNull((string) $cells[1]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[2]->getValue());
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams['category'.$rowNumber] = $this->stringOrNull((string) $cells[3]->getValue());
                $sqlParams['event'.$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams['meta'.$rowNumber] = $this->stringOrNull((string) $cells[5]->getValue());
                $sqlParams['source'.$rowNumber] = $this->stringOrNull((string) $cells[6]->getValue());

                if ($rowNumber % $nbByBatch == 0) {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);

                    $sqlParams = [];
                    $sql = $sqlBase;

                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                }
            }
        }

        // sauvegarde
        if (count($sqlParams) > 0) {
            $sql = substr($sql, 0, -1);
            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
            $stmt->execute($sqlParams);
        }
        
        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}