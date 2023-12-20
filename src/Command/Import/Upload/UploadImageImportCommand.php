<?php

namespace App\Command\Import\Upload;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:upload_image', description: 'Import upload image')]
class UploadImageImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import upload image';
    protected string $commandTextEnd = '<Import upload image';

    protected function import($input, $output)
    {
        // ==================================================================
        // UPLOAD IMAGE
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('UPLOAD IMAGE');

        // fichier
        $filePath = $this->findCsvFile('upload_uploadimage_');
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
        
        $sqlBase = "INSERT INTO `upload_image`
                    (
                    `id`,
                    `image`,
                    `description`,
                    time_create
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
                    :image".$rowNumber.",
                    :description".$rowNumber.",
                    :time_create".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["image".$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams["description".$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[3]->getValue());
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');


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