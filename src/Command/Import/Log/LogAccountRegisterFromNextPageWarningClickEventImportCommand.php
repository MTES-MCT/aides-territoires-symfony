<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:log_arfnpwce_click', description: 'Import log AccountRegisterFromNextPageWarningClickEvent')]
class LogAccountRegisterFromNextPageWarningClickEventImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log AccountRegisterFromNextPageWarningClickEvent';
    protected string $commandTextEnd = '<Import log AccountRegisterFromNextPageWarningClickEvent';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG AccountRegisterFromNextPageWarningClickEvent
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG AccountRegisterFromNextPageWarningClickEvent');

        // fichier
        $filePath = $this->findCsvFile('stats_accountregisterfromnextpagewarningclickevent_');
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

        $sqlBase = "INSERT INTO `log_account_register_from_next_page_warning_click_event`
        (
        `id`,
        querystring,
        time_create,
        date_create
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
                    :querystring".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber."
                ),";

                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams['querystring'.$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[2]->getValue());
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');


                if ($rowNumber % $nbByBatch == 0) {
                    try {
                        if (count($sqlParams) > 0) {
                            $sql = substr($sql, 0, -1);

                            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                            $stmt->execute($sqlParams);
                        }
                        $sqlParams = [];
                        $sql = $sqlBase;
    
                        // advances the progress bar 1 unit
                        $io->progressAdvance();
                    } catch (\Exception $e) {
                    }

                }
            }
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