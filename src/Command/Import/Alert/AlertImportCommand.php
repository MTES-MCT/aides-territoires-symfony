<?php

namespace App\Command\Import\Alert;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: 'at:import:alert', description: 'Import alert')]
class AlertImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import alert';
    protected string $commandTextEnd = '<Import alert';

    protected function import($input, $output)
    {
        // ==================================================================
        // ALERT
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('ALERT');

        // fichier
        $filePath = $this->findCsvFile('alerts_alert_');
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

        $sqlBase = "INSERT INTO `alert`
        (
        id,
        email,
        querystring,
        title,
        alert_frequency,
        validated,
        time_latest_alert,
        date_latest_alert,
        time_create,
        time_update,
        time_validated,
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
                    :email".$rowNumber.",
                    :querystring".$rowNumber.",
                    :title".$rowNumber.",
                    :alert_frequency".$rowNumber.",
                    :validated".$rowNumber.",
                    :time_latest_alert".$rowNumber.",
                    :date_latest_alert".$rowNumber.",
                    :time_create".$rowNumber.",
                    :time_update".$rowNumber.",
                    :time_validated".$rowNumber.",
                    :source".$rowNumber."   
                ),";

                $sqlParams['id'.$rowNumber] = Uuid::fromRfc4122((string) $cells[0]->getValue())->toBinary();
                $sqlParams['email'.$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams['querystring'.$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams['title'.$rowNumber] = $this->stringOrNull((string) $cells[3]->getValue());
                $sqlParams['alert_frequency'.$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams['validated'.$rowNumber] = $this->stringToBool((string) $cells[5]->getValue());
                $timeLastAlert = $this->stringToDateTimeOrNull((string) $cells[6]->getValue());
                $sqlParams['time_latest_alert'.$rowNumber] = $timeLastAlert ? $timeLastAlert->format('Y-m-d H:i:s') : null;
                $sqlParams['date_latest_alert'.$rowNumber] = $timeLastAlert ? $timeLastAlert->format('Y-m-d') : null;
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[7]->getValue());
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $timeUpdate = $this->stringToDateTimeOrNull((string) $cells[8]->getValue());
                $sqlParams['time_update'.$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;
                $timeValidated = $this->stringToDateTimeOrNull((string) $cells[9]->getValue());
                $sqlParams['time_validated'.$rowNumber] = $timeValidated ? $timeValidated->format('Y-m-d H:i:s') : null;
                $sqlParams['source'.$rowNumber] = $this->stringOrNull((string) $cells[10]->getValue());

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