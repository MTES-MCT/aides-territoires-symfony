<?php

namespace App\Command\Import\Project;

use App\Command\Import\ImportCommand;
use App\Entity\User\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:project', description: 'Import project')]
class ProjectImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import project';
    protected string $commandTextEnd = '<Import project';

    protected function import($input, $output)
    {
        // ==================================================================
        // PROJECT
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PROJECT');

        // fichier
        $filePath = $this->findCsvFile('projects_project_');
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
        $nbByBatch = 2000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO project (
            `id`,
            `name`,
            slug,
            `description`,
            time_create,
            date_create,
            key_words,
            due_date,
            contract_link,
            is_public,
            private_description,
            project_types_suggestion,
            `status`,
            budget,
            other_project_owner,
            step,
            `image`
        ) VALUES ";
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
                $sql .= "(
                    :id".$rowNumber.",
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :description".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :key_words".$rowNumber.",
                    :due_date".$rowNumber.",
                    :contract_link".$rowNumber.",
                    :is_public".$rowNumber.",
                    :private_description".$rowNumber.",
                    :project_types_suggestion".$rowNumber.",
                    :status".$rowNumber.",
                    :budget".$rowNumber.",
                    :other_project_owner".$rowNumber.",
                    :step".$rowNumber.",
                    :image".$rowNumber."
                ),";

                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["slug".$rowNumber] = (string) $cells[2]->getValue();
                $sqlParams["description".$rowNumber] = $this->stringOrNull((string) $cells[3]->getValue());

                try {
                    $timeCreate = new \DateTime(date((string) $cells[4]->getValue()));
                } catch (\Exception $exception) {
                    $timeCreate = new \DateTime(date('Y-m-d H:i:s'));
                }
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams["date_create".$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams["key_words".$rowNumber] = $this->stringOrNull((string) $cells[5]->getValue());
                try {
                    $dueDate = new \DateTime(date((string) $cells[6]->getValue()));
                } catch (\Exception $exception) {
                    $dueDate = null;
                }
                $sqlParams["due_date".$rowNumber] = $dueDate ? $dueDate->format('Y-m-d') : null;
                $sqlParams["contract_link".$rowNumber] = $this->stringOrNull((string) $cells[7]->getValue());
                $sqlParams["is_public".$rowNumber] = $this->stringToBool((string) $cells[8]->getValue());
                $sqlParams["private_description".$rowNumber] = $this->stringOrNull((string) $cells[9]->getValue());
                $sqlParams["project_types_suggestion".$rowNumber] = $this->stringOrNull((string) $cells[10]->getValue());
                $sqlParams["status".$rowNumber] = (string) $cells[11]->getValue();
                $sqlParams["budget".$rowNumber] = $this->intOrNull((string) $cells[12]->getValue());
                $sqlParams["other_project_owner".$rowNumber] = $this->stringOrNull((string) $cells[13]->getValue());
                $sqlParams["step".$rowNumber] = $this->stringOrNull((string) $cells[14]->getValue());
                $sqlParams["image".$rowNumber] = $this->stringOrNull((string) $cells[15]->getValue());


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
        // TABLEAU DES USERS
        // ==================================================================

        $users = $this->managerRegistry->getRepository(User::class)->findAll();
        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
        }
        unset($users);

        // ==================================================================
        // PROJECT AUTHOR
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PROJECT AUTHOR');

        // fichier
        $filePath = $this->findCsvFile('projects_project_author_');
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

        // progressbar
        $io->createProgressBar($nbToImport);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "UPDATE project SET author_id = :author_id WHERE id=:id;";
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

                if (isset($usersById[(int) $cells[2]->getValue()])) {
                    $sqlParams["id"] = (int) $cells[1]->getValue();
                    $sqlParams["author_id"] = (int) $cells[2]->getValue();
                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);
                }
                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();


        // ==================================================================
        // PROJECT ORGANIZATION
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PROJECT ORGANIZATION');

        // fichier
        $filePath = $this->findCsvFile('projects_project_organizations_');
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

        // progressbar
        $io->createProgressBar($nbToImport);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "UPDATE project SET organization_id = :organization_id WHERE id=:id;";
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

                $sqlParams["id"] = (int) $cells[1]->getValue();
                $sqlParams["organization_id"] = (int) $cells[2]->getValue();
                $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                $stmt->execute($sqlParams);

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // PROJECT SYNONYM
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PROJECT SYNONYM');

        // fichier
        $filePath = $this->findCsvFile('projects_project_project_types_');
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

        // progressbar
        $io->createProgressBar($nbToImport);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO project_keyword_synonymlist
        (
        project_id,
        keyword_synonymlist_id
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
                    :project_id".$rowNumber.",
                    :keyword_synonymlist_id".$rowNumber."
                ),";

                $sqlParams['project_id'.$rowNumber] = (int) $cells[1]->getValue();
                $sqlParams['keyword_synonymlist_id'.$rowNumber] = (int) $cells[2]->getValue();
                
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

        // sauvegarde
        // $this->managerRegistry->getManager()->flush();
        if (count($sqlParams) > 0) {
            $sql = substr($sql, 0, -1);
            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
            $stmt->execute($sqlParams);
        }
        
        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // PROJECT VALIDATED
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PROJECT VALIDATED');

        // fichier
        $filePath = $this->findCsvFile('projects_validatedproject_');
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

        // progressbar
        $io->createProgressBar($nbToImport);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO project_validated
        (
        `id`,
        aid_id,
        financer_id,
        organization_id,
        project_id,
        project_name,
        `description`,
        aid_name,
        financer_name,
        budget,
        amount_obtained,
        time_obtained,
        time_create,
        date_create,
        import_uniqueid
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
                    :financer_id".$rowNumber.",
                    :organization_id".$rowNumber.",
                    :project_id".$rowNumber.",
                    :project_name".$rowNumber.",
                    :description".$rowNumber.",
                    :aid_name".$rowNumber.",
                    :financer_name".$rowNumber.",
                    :budget".$rowNumber.",
                    :amount_obtained".$rowNumber.",
                    :time_obtained".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :import_uniqueid".$rowNumber."
                ),";

                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams['project_name'.$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams['description'.$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams['aid_name'.$rowNumber] = $this->stringOrNull((string) $cells[3]->getValue());
                $sqlParams['financer_name'.$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams['budget'.$rowNumber] = $this->intOrNull((string) $cells[5]->getValue());
                $sqlParams['amount_obtained'.$rowNumber] = $this->intOrNull((string) $cells[6]->getValue());
                try {
                    $timeObtained = new \DateTime(date((string) $cells[7]->getValue()));
                } catch (\Exception $exception) {
                    $timeObtained = null;
                }
                $sqlParams['time_obtained'.$rowNumber] = $timeObtained ? $timeObtained->format('Y-m-d H:i:s') : null;
                try {
                    $timeCreate = new \DateTime(date((string) $cells[8]->getValue()));
                } catch (\Exception $exception) {
                    $timeCreate = new \DateTime(date('Y-m-d H:i:s'));
                }
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams['import_uniqueid'.$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams['aid_id'.$rowNumber] = $this->intOrNull((string) $cells[11]->getValue());
                $sqlParams['financer_id'.$rowNumber] = $this->intOrNull((string) $cells[12]->getValue());
                $sqlParams['organization_id'.$rowNumber] = $this->intOrNull((string) $cells[13]->getValue());
                $sqlParams['project_id'.$rowNumber] = $this->intOrNull((string) $cells[14]->getValue());

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

        // sauvegarde
        // $this->managerRegistry->getManager()->flush();
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