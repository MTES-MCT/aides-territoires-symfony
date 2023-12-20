<?php

namespace App\Command\Import\Page;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:page', description: 'Import page')]
class PageImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import page';
    protected string $commandTextEnd = '<Import page';

    protected function import($input, $output)
    {
        // ==================================================================
        // PAGE FAQ CATEGORY
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PAGE FAQ CATEGORY');

        // fichier
        $filePath = $this->findCsvFile('pages_faqcategory_');
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
        
        $sqlBase = "INSERT INTO `faq_category`
                    (
                    `id`,
                    `name`,
                    `position`,
                    time_create,
                    time_update
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
                    :position".$rowNumber.",
                    :time_create".$rowNumber.",
                    :time_update".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams["position".$rowNumber] = (int) $cells[2]->getValue();
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[3]->getValue());
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $timeUpdate = $this->stringToDateTimeOrNull((string) $cells[4]->getValue());
                $sqlParams["time_update".$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;


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
        // PAGE FAQ ANSWER
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PAGE FAQ ANSWER');

        // fichier
        $filePath = $this->findCsvFile('pages_faqquestionanswer_');
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
        
        $sqlBase = "INSERT INTO `faq_question_answser`
                    (
                    `id`,
                    faq_category_id,
                    program_id,
                    question,
                    answer,
                    `position`,
                    time_create,
                    time_update
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
                    :faq_category_id".$rowNumber.",
                    :program_id".$rowNumber.",
                    :question".$rowNumber.",
                    :answer".$rowNumber.",
                    :position".$rowNumber.",
                    :time_create".$rowNumber.",
                    :time_update".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["question".$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams["answer".$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams["position".$rowNumber] = (int) $cells[3]->getValue();
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[4]->getValue());
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $timeUpdate = $this->stringToDateTimeOrNull((string) $cells[5]->getValue());
                $sqlParams["time_update".$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;
                $sqlParams['faq_category_id'.$rowNumber] = $this->intOrNull((string) $cells[6]->getValue());
                $sqlParams['program_id'.$rowNumber] = $this->intOrNull((string) $cells[7]->getValue());

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
        // PAGE
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PAGE');

        // fichier
        $filePath = $this->findCsvFile('pages_page_');
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
        
        $sqlBase = "INSERT INTO `page`
                    (
                    `id`,
                    `url`,
                    search_page_id,
                    meta_title,
                    meta_description,
                    time_create,
                    time_update
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
                    :url".$rowNumber.",
                    :search_page_id".$rowNumber.",
                    :meta_title".$rowNumber.",
                    :meta_description".$rowNumber.",
                    :time_create".$rowNumber.",
                    :time_update".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["url".$rowNumber] = uniqid();
                $sqlParams["meta_title".$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams["meta_description".$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams["search_page_id".$rowNumber] = $this->intOrNull((string) $cells[3]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[4]->getValue());
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $timeUpdate = $this->stringToDateTimeOrNull((string) $cells[5]->getValue());
                $sqlParams["time_update".$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;

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
        // FLAT PAGE
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('FLAT PAGE');

        // fichier
        $filePath = $this->findCsvFile('django_flatpage_');
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
                $sqlParams = [];
                $sql = "
                    UPDATE page SET
                        url = :url,
                        name = :name,
                        description = :description,
                        enable_comments = :enable_comments,
                        registration_required = :registration_required
                    WHERE id = :id;
                ";

                $sqlParams['id'] = (int) $cells[0]->getValue();
                $sqlParams['url'] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams['name'] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams['description'] = $this->stringOrNull((string) $cells[3]->getValue());
                $sqlParams['enable_comments'] = $this->stringToBool((string) $cells[4]->getValue());
                $sqlParams['registration_required'] = $this->stringToBool((string) $cells[6]->getValue());


                $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                $stmt->execute($sqlParams);

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }


        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // PAGE TAB
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PAGE TAB');

        // fichier
        $filePath = $this->findCsvFile('pages_tab_');
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
        
        $sqlBase = "INSERT INTO `page_tab`
                    (
                    `id`,
                    program_id,
                    `name`,
                    `description`,
                    time_create,
                    time_update
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
                    :program_id".$rowNumber.",
                    :name".$rowNumber.",
                    :description".$rowNumber.",
                    :time_create".$rowNumber.",
                    :time_update".$rowNumber."
                ),";


                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams['name'.$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams['description'.$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[3]->getValue());
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $timeUpdate = $this->stringToDateTimeOrNull((string) $cells[4]->getValue());
                $sqlParams["time_update".$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;
                $sqlParams['program_id'.$rowNumber] = $this->intOrNull((int) $cells[5]->getValue());

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