<?php

namespace App\Command\Import\User;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:user_notification', description: 'Import user notification')]
class NotificationImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import user notification';
    protected string $commandTextEnd = '<Import user notification';

    protected function import($input, $output)
    {
        // ==================================================================
        // USER NOTIFICATION
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('USER NOTIFICATION');

        // fichier
        $filePath = $this->findCsvFile('notifications_notification_');
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
        $nbByBatch = 5000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO `notification`
                    (
                    `id`,
                    `user_id`,
                    `name`,
                    `description`,
                    time_create,
                    time_read,
                    time_email
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

                // entite

                $sql .= "
                (
                    :id".$rowNumber.",
                    :user_id".$rowNumber.",
                    :name".$rowNumber.",
                    :description".$rowNumber.",
                    :time_create".$rowNumber.",
                    :time_read".$rowNumber.",
                    :time_email".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams["description".$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[3]->getValue());
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $timeRead = $this->stringToDateTimeOrNull((string) $cells[4]->getValue());
                $sqlParams["time_read".$rowNumber] = $timeRead ? $timeRead->format('Y-m-d H:i:s') : null;
                $timeEmail = $this->stringToDateTimeOrNull((string) $cells[5]->getValue());
                $sqlParams["time_email".$rowNumber] = $timeEmail ? $timeEmail->format('Y-m-d H:i:s') : null;
                $sqlParams["user_id".$rowNumber] = (int) $cells[6]->getValue();

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