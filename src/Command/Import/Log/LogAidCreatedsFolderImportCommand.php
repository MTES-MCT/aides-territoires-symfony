<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:log_aid_createds_folder', description: 'Import log aid createds folder')]
class LogAidCreatedsFolderImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log aid createds folder';
    protected string $commandTextEnd = '<Import log aid createds folder';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG AID CREATEDS FOLDER
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG AID CREATEDS FOLDER');

        // fichier
        $filePath = $this->findCsvFile('stats_aidcreatedsfolderevent_');
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

        $sqlBase = "INSERT INTO `log_aid_createds_folder`
        (
        aid_id,
        organization_id,
        user_id,
        ds_folder_url,
        ds_folder_id,
        ds_folder_number,
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
                    :organization_id".$rowNumber.",
                    :user_id".$rowNumber.",
                    :ds_folder_url".$rowNumber.",
                    :ds_folder_id".$rowNumber.",
                    :ds_folder_number".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber."
                ),";

                $sqlParams['ds_folder_url'.$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams['ds_folder_id'.$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams['ds_folder_number'.$rowNumber] = $this->intOrNull((string) $cells[3]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[4]->getValue());
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams['aid_id'.$rowNumber] = $this->intOrNull((string) $cells[5]->getValue());
                $sqlParams['organization_id'.$rowNumber] = $this->intOrNull((string) $cells[6]->getValue());
                $sqlParams['user_id'.$rowNumber] = $this->intOrNull((string) $cells[7]->getValue());


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