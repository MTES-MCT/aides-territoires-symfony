<?php

namespace App\Command\Import\Aid;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:aid_project', description: 'Import aid project')]
class AidProjectImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import aid project';
    protected string $commandTextEnd = '<Import aid project';

    protected function import($input, $output)
    {
        // ==================================================================
        // AID PROJECT
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID PROJECT');

        // fichier
        $filePath = $this->findCsvFile('aids_aidproject_');
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
        
        $sqlBase = "INSERT INTO `aid_project`
        (
        `id`,
        aid_id,
        creator_id,
        project_id,
        time_create,
        date_create,
        aid_denied,
        aid_obtained,
        aid_paid,
        aid_requested,
        time_denied,
        time_obtained,
        time_paid,
        time_requested
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
                    :aid_id".$rowNumber.",
                    :creator_id".$rowNumber.",
                    :project_id".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :aid_denied".$rowNumber.",
                    :aid_obtained".$rowNumber.",
                    :aid_paid".$rowNumber.",
                    :aid_requested".$rowNumber.",
                    :time_denied".$rowNumber.",
                    :time_obtained".$rowNumber.",
                    :time_paid".$rowNumber.",
                    :time_requested".$rowNumber."    
                ),";

                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                try {
                    $timeCreate = new \DateTime(date((string) $cells[1]->getValue()));
                } catch (\Exception $exception) {
                    $timeCreate = new \DateTime(date('Y-m-d H:i:s'));
                }
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                
                $sqlParams['aid_id'.$rowNumber] = (int) $cells[2]->getValue();
                $sqlParams['creator_id'.$rowNumber] = $this->intOrNull((string) $cells[3]->getValue());
                $sqlParams['project_id'.$rowNumber] = (int) $cells[4]->getValue();
                $sqlParams['aid_denied'.$rowNumber] = $this->stringToBool((string) $cells[5]->getValue());
                $sqlParams['aid_obtained'.$rowNumber] = $this->stringToBool((string) $cells[6]->getValue());
                $sqlParams['aid_paid'.$rowNumber] = $this->stringToBool((string) $cells[7]->getValue());
                $sqlParams['aid_requested'.$rowNumber] = $this->stringToBool((string) $cells[8]->getValue());
                $timeDenied = $this->stringToDateTimeOrNull((string) $cells[9]->getValue());
                $sqlParams['time_denied'.$rowNumber] = $timeDenied ? $timeDenied->format('Y-m-d H:i:s') : null;
                $timeObtained = $this->stringToDateTimeOrNull((string) $cells[10]->getValue());
                $sqlParams['time_obtained'.$rowNumber] = $timeObtained ? $timeObtained->format('Y-m-d H:i:s') : null;
                $timePaid = $this->stringToDateTimeOrNull((string) $cells[11]->getValue());
                $sqlParams['time_paid'.$rowNumber] = $timePaid ? $timePaid->format('Y-m-d H:i:s') : null;
                $timeRequested = $this->stringToDateTimeOrNull((string) $cells[12]->getValue());
                $sqlParams['time_requested'.$rowNumber] = $timeRequested ? $timeRequested->format('Y-m-d H:i:s') : null;



                if ($rowNumber % $nbByBatch == 0 && count($sqlParams) > 0) {
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

        // ==================================================================
        // AID SUGGESTED AID PROJECT
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID SUGGESTED AID PROJECT');

        // fichier
        $filePath = $this->findCsvFile('aids_suggestedaidproject_');
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
        
        $sqlBase = "INSERT INTO `aid_suggested_aid_project`
        (
        `id`,
        time_create,
        date_create,
        aid_id,
        creator_id,
        project_id,
        time_associated,
        is_associated,
        time_rejected,
        is_rejected
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
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :aid_id".$rowNumber.",
                    :creator_id".$rowNumber.",
                    :project_id".$rowNumber.",
                    :time_associated".$rowNumber.",
                    :is_associated".$rowNumber.",
                    :time_rejected".$rowNumber.",
                    :is_rejected".$rowNumber." 
                ),";

                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                try {
                    $timeCreate = new \DateTime(date((string) $cells[1]->getValue()));
                } catch (\Exception $exception) {
                    $timeCreate = new \DateTime(date('Y-m-d H:i:s'));
                }
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                
                $sqlParams['aid_id'.$rowNumber] = (int) $cells[2]->getValue();
                $sqlParams['creator_id'.$rowNumber] = $this->intOrNull((string) $cells[3]->getValue());
                $sqlParams['project_id'.$rowNumber] = (int) $cells[4]->getValue();
                $timeAssociated = $this->stringToDateTimeOrNull((string) $cells[5]->getValue());
                $sqlParams['time_associated'.$rowNumber] = $timeAssociated ? $timeAssociated->format('Y-m-d H:i:s') : null;
                $sqlParams['is_associated'.$rowNumber] = $this->stringToBool((string) $cells[6]->getValue());
                $timeRejected = $this->stringToDateTimeOrNull((string) $cells[7]->getValue());
                $sqlParams['time_rejected'.$rowNumber] = $timeRejected ? $timeRejected->format('Y-m-d H:i:s') : null;
                $sqlParams['is_rejected'.$rowNumber] = $this->stringToBool((string) $cells[8]->getValue());


                if ($rowNumber % $nbByBatch == 0 && count($sqlParams) > 0) {
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
        

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}