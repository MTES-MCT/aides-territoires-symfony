<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:log_aid_origin_url_click', description: 'Import log aid origin url click')]
class LogAidOriginUrlClickImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log aid origin url click';
    protected string $commandTextEnd = '<Import log aid origin url click';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG AID ORIGIN URL CLICK
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG AID ORIGIN URL CLICK');

        // fichier
        $filePath = $this->findCsvFile('stats_aidoriginurlclickevent_');
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

        $sqlBase = "INSERT INTO `log_aid_origin_url_click`
        (
        aid_id,
        querystring,
        `source`,
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
                    :aid_id".$rowNumber.",
                    :querystring".$rowNumber.",
                    :source".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber."
                ),";

                $sqlParams['querystring'.$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams['source'.$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[3]->getValue());
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams['aid_id'.$rowNumber] = $this->intOrNull((string) $cells[4]->getValue());


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