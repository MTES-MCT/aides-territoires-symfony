<?php

namespace App\Command\Import\Category;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:category', description: 'Import category')]
class CategoryImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import category';
    protected string $commandTextEnd = '<Import category';

    protected function import($input, $output)
    {

        // ==================================================================
        // CATEGORY THEME
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('CATEGORY THEME');

        // fichier
        $filePath = $this->findCsvFile('categories_theme_');
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
        $nbByBatch = 10000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO `category_theme`
        (
        `id`,
        `name`,
        slug,
        short_description
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
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :short_description".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["short_description".$rowNumber] = (string) $cells[2]->getValue();
                $sqlParams["slug".$rowNumber] = (string) $cells[3]->getValue();
                
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
        // category
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('category');

        // fichier
        $filePath = $this->findCsvFile('categories_category_');
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
        $nbByBatch = 10000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO `category`
        (
        `id`,
        category_theme_id,
        `name`,
        slug,
        short_description
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
                    :category_theme_id".$rowNumber.",
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :short_description".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["short_description".$rowNumber] = (string) $cells[2]->getValue();
                $sqlParams["category_theme_id".$rowNumber] = (int) $cells[3]->getValue();
                $sqlParams["slug".$rowNumber] = (string) $cells[4]->getValue();
                
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