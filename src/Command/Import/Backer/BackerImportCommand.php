<?php

namespace App\Command\Import\Backer;

use App\Command\Import\ImportCommand;
use App\Entity\Backer\BackerGroup;
use App\Entity\Perimeter\Perimeter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:backer', description: 'Import backer')]
class BackerImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import backer';
    protected string $commandTextEnd = '<Import backer';

    protected function import($input, $output)
    {
        // ==================================================================
        // BACKER CATEGORY
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('BACKER CATEGORY');

        // fichier
        $filePath = $this->findCsvFile('backers_backercategory_');
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
        
        $sqlBase = "INSERT INTO `backer_category`
                    (
                    `id`,
                    `name`,
                    slug,
                    `position`,
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
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :position".$rowNumber.",
                    :time_create".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["slug".$rowNumber] = (string) $cells[2]->getValue();
                $sqlParams["position".$rowNumber] = (int) $cells[3]->getValue();

                try {
                    $timeCrate = new \DateTime(date((string) $cells[4]->getValue()));
                } catch (\Exception $exception) {
                    $timeCrate = new \DateTime(date('Y-m-d H:i:s'));
                }

                $sqlParams["time_create".$rowNumber] = $timeCrate->format('Y-m-d H:i:s');

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
        // BACKER SUBCATEGORY
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('BACKER SUBCATEGORY');

        // fichier
        $filePath = $this->findCsvFile('backers_backersubcategory_');
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
        
        $sqlBase = "INSERT INTO `backer_subcategory`
                    (
                    `id`,
                    backer_category_id,
                    `name`,
                    slug,
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
                    :backer_category_id".$rowNumber.",
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :time_create".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["slug".$rowNumber] = (string) $cells[2]->getValue();

                try {
                    $timeCrate = new \DateTime(date((string) $cells[3]->getValue()));
                } catch (\Exception $exception) {
                    $timeCrate = new \DateTime(date('Y-m-d H:i:s'));
                }

                $sqlParams["time_create".$rowNumber] = $timeCrate->format('Y-m-d H:i:s');
                $sqlParams["backer_category_id".$rowNumber] = (int) $cells[4]->getValue();               

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
        // BACKER GROUP
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('BACKER GROUP');

        // fichier
        $filePath = $this->findCsvFile('backers_backergroup_');
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
        
        $sqlBase = "INSERT INTO `backer_group`
                    (
                    `id`,
                    backer_sub_category_id,
                    `name`,
                    slug,
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
                    :backer_sub_category_id".$rowNumber.",
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :time_create".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["slug".$rowNumber] = (string) $cells[2]->getValue();


                try {
                    $timeCrate = new \DateTime(date((string) $cells[3]->getValue()));
                } catch (\Exception $exception) {
                    $timeCrate = new \DateTime(date('Y-m-d H:i:s'));
                }

                $sqlParams["time_create".$rowNumber] = $timeCrate->format('Y-m-d H:i:s');
                $sqlParams["backer_sub_category_id".$rowNumber] = $this->intOrNull((string) $cells[4]->getValue());

                
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
        // BACKER
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('BACKER');

        // fichier
        $filePath = $this->findCsvFile('backers_backer_');
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
        
        $sqlBase = "INSERT INTO `backer`
                    (
                    `id`,
                    perimeter_id, backer_group_id, `name`, slug, is_corporate,
                    external_link, is_spotlighted, logo, `description`,
                    time_create, meta_description, meta_title
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
                    :perimeter_id".$rowNumber.",
                    :backer_group_id".$rowNumber.",
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :is_corporate".$rowNumber.",
                    :external_link".$rowNumber.",
                    :is_spotlighted".$rowNumber.",
                    :logo".$rowNumber.",
                    :description".$rowNumber.",
                    :time_create".$rowNumber.",
                    :meta_description".$rowNumber.",
                    :meta_title".$rowNumber."
                ),";


                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["is_corporate".$rowNumber] = $this->stringToBool((string) $cells[2]->getValue());
                $sqlParams["slug".$rowNumber] = (string) $cells[3]->getValue();
                $sqlParams["external_link".$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams["is_spotlighted".$rowNumber] = $this->stringToBool((string) $cells[5]->getValue());
                $sqlParams["logo".$rowNumber] = $this->stringOrNull((string) $cells[6]->getValue());
                $sqlParams["description".$rowNumber] = $this->stringOrNull((string) $cells[7]->getValue());
                $sqlParams['backer_group_id'.$rowNumber] = $this->intOrNull((string) $cells[8]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[9]->getValue());
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams["meta_description".$rowNumber] = $this->stringOrNull((string) $cells[10]->getValue());
                $sqlParams["meta_title".$rowNumber] = $this->stringOrNull((string) $cells[11]->getValue());
                $sqlParams["perimeter_id".$rowNumber] = $this->intOrNull((string) $cells[12]->getValue());
                


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