<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:log_aid_eligibility_test', description: 'Import log aid eligibility test')]
class LogAidEligibilityTestImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log aid eligibility test';
    protected string $commandTextEnd = '<Import log aid eligibility test';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG AID ELIGIBILITY TEST
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG AID ELIGIBILITY TEST');

        // fichier
        $filePath = $this->findCsvFile('stats_aideligibilitytestevent_');
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
        $nbByBatch = 1;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();

        $sqlBase = "INSERT INTO `log_aid_eligibility_test`
        (
        aid_id,
        eligibility_test_id,
        answer_success,
        answer_details,
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
                    :eligibility_test_id".$rowNumber.",
                    :answer_success".$rowNumber.",
                    :answer_details".$rowNumber.",
                    :querystring".$rowNumber.",
                    :source".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber."
                ),";

                $sqlParams['answer_success'.$rowNumber] = $this->stringToBool((string) $cells[1]->getValue());
                $sqlParams['answer_details'.$rowNumber] = (string) $cells[2]->getValue();
                $sqlParams['querystring'.$rowNumber] = $this->stringOrNull((string) $cells[3]->getValue());
                $sqlParams['source'.$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[5]->getValue());
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams['aid_id'.$rowNumber] = $this->intOrNull((string) $cells[6]->getValue());
                $sqlParams['eligibility_test_id'.$rowNumber] = $this->intOrNull((string) $cells[7]->getValue());


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
                        dump($sql);
                        dump($sqlParams);
                        dump($e);exit;
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
            dump($sql);
            dump($sqlParams);
            dump($e);exit;
        }
        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}