<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:log_admin_action', description: 'Import log admin action')]
class LogAdminActionImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log admin action';
    protected string $commandTextEnd = '<Import log admin action';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG ADMIN ACTION
        // ==================================================================

        // $io = new SymfonyStyle($input, $output);
        // $io->info('LOG ADMIN ACTION');

        // // fichier
        // $filePath = $this->findCsvFile('exporting_dataexport_');
        // if (!$filePath) {
        //     throw new \Exception('File missing');
        // }
        // $reader = ReaderEntityFactory::createReaderFromFile($filePath);
        // $reader->setFieldDelimiter(';');
        // $reader->setFieldEnclosure('"');

        // // ouverture
        // $reader->open($filePath);

        // // estime le nombre de lignes à importé
        // $file = new \SplFileObject($filePath, 'r');
        // $file->seek(PHP_INT_MAX);
        // $nbToImport = $file->key() + 1;
        // unset($file);

        // // batch
        // $nbByBatch = 3000;
        // $nbBatch = ceil($nbToImport / $nbByBatch);

        // // progressbar
        // $io->createProgressBar($nbBatch);

        // // starts and displays the progress bar
        // $io->progressStart();

        // $sqlBase = "INSERT INTO `data_export`
        // (
        // `id`,
        // author_id,
        // exported_file,
        // time_create
        // )
        // VALUES ";
        // $sql = $sqlBase;
        // $sqlParams = [];
        // $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);

        // // importe les lignes
        // foreach ($reader->getSheetIterator() as $sheet) {
        //     foreach ($sheet->getRowIterator() as $rowNumber => $row) {
        //         if ($rowNumber == 1) {
        //             continue;
        //         }
        //         // do stuff with the row
        //         $cells = $row->getCells();

        //         $sql .= "
        //         (
        //             :id".$rowNumber.",
        //             :author_id".$rowNumber.",
        //             :exported_file".$rowNumber.",
        //             :time_create".$rowNumber."
        //         ),";

        //         $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
        //         $sqlParams['exported_file'.$rowNumber] = (string) $cells[1]->getValue();
        //         $sqlParams['time_create'.$rowNumber] = $this->stringToDateTimeOrNow((string) $cells[2]->getValue())->format('Y-m-d H:i:s');
        //         $sqlParams['author_id'.$rowNumber] = (int) $cells[3]->getValue();

        //         if ($rowNumber % $nbByBatch == 0) {
        //             $sql = substr($sql, 0, -1);

        //             $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
        //             $stmt->execute($sqlParams);

        //             $sqlParams = [];
        //             $sql = $sqlBase;

        //             // advances the progress bar 1 unit
        //             $io->progressAdvance();
        //         }
        //     }
        // }

        // // sauvegarde
        // if (count($sqlParams) > 0) {
        //     $sql = substr($sql, 0, -1);
        //     $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
        //     $stmt->execute($sqlParams);
        // }
        
        // // ensures that the progress bar is at 100%
        // $io->progressFinish();

        // // ==================================================================
        // // success
        // // ==================================================================
        // $io->success('import ok');
    }

}