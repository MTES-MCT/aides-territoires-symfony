<?php

namespace App\Command\Import\DataSource;

use App\Command\Import\ImportCommand;
use App\Entity\Backer\Backer;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:data_source', description: 'Import data source')]
class DataSourceImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import data source';
    protected string $commandTextEnd = '<Import data source';

    protected function import($input, $output)
    {
        // ==================================================================
        // tableau périmètres
        // ==================================================================
        $perimeters = $this->managerRegistry->getRepository(Perimeter::class)->findAll();
        $perimetersById = [];
        foreach ($perimeters as $perimeter) {
            $perimetersById[$perimeter->getId()] = $perimeter;
        }
        unset($perimeters);

        // ==================================================================
        // tableau backers
        // ==================================================================
        $backers = $this->managerRegistry->getRepository(Backer::class)->findAll();
        $backersById = [];
        foreach ($backers as $backer) {
            $backersById[$backer->getId()] = $backer;
        }
        unset($backers);

        // ==================================================================
        // tableau users
        // ==================================================================

        $usersById = [];


        // ==================================================================
        // BACKER CATEGORY
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('DATA SOURCE');

        // fichier
        $filePath = $this->findCsvFile('dataproviders_datasource_');
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
        
        $sqlBase = "INSERT INTO `data_source`
                    (
                    `id`,
                    backer_id,
                    contact_team_id,
                    perimeter_id,
                    aid_author_id,
                    `name`,
                    `description`,
                    import_details,
                    import_api_url,
                    import_data_url,
                    import_licence,
                    contact_backer,
                    time_create,
                    time_update,
                    time_last_access
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
                    :backer_id".$rowNumber.",
                    :contact_team_id".$rowNumber.",
                    :perimeter_id".$rowNumber.",
                    :aid_author_id".$rowNumber.",
                    :name".$rowNumber.",
                    :description".$rowNumber.",
                    :import_details".$rowNumber.",
                    :import_api_url".$rowNumber.",
                    :import_data_url".$rowNumber.",
                    :import_licence".$rowNumber.",
                    :contact_backer".$rowNumber.",
                    :time_create".$rowNumber.",
                    :time_update".$rowNumber.",
                    :time_last_access".$rowNumber."                    
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["backer_id".$rowNumber] = isset($backersById[(int) $cells[11]->getValue()]) ? (int) $cells[11]->getValue() : null;
                $sqlParams["perimeter_id".$rowNumber] = isset($perimetersById[(int) $cells[13]->getValue()]) ? (int) $cells[13]->getValue() : null;
                if (!isset($usersById[(int) $cells[12]->getValue()])) {
                    $usersById[(int) $cells[12]->getValue()] = $this->managerRegistry->getRepository(User::class)->find((int) $cells[12]->getValue());
                }
                $sqlParams['contact_team_id'.$rowNumber] = $usersById[(int) $cells[12]->getValue()] instanceof User ? (int) $cells[12]->getValue() : null;

                if (!isset($usersById[(int) $cells[14]->getValue()])) {
                    $usersById[(int) $cells[14]->getValue()] = $this->managerRegistry->getRepository(User::class)->find((int) $cells[14]->getValue());
                }
                $sqlParams['aid_author_id'.$rowNumber] = $usersById[(int) $cells[14]->getValue()] instanceof User ? (int) $cells[14]->getValue() : null;

                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["description".$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams["import_details".$rowNumber] = $this->stringOrNull((string) $cells[3]->getValue());
                $sqlParams["import_api_url".$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams["import_data_url".$rowNumber] = $this->stringOrNull((string) $cells[5]->getValue());
                $sqlParams["import_licence".$rowNumber] = $this->stringOrNull((string) $cells[6]->getValue());
                $sqlParams["contact_backer".$rowNumber] = $this->stringOrNull((string) $cells[7]->getValue());

                try {
                    $timeCreate = new \DateTime(date((string) $cells[8]->getValue()));
                } catch (\Exception $exception) {
                    $timeCreate = new \DateTime(date('Y-m-d H:i:s'));
                }
                try {
                    $timeUpdate = new \DateTime(date((string) $cells[9]->getValue()));
                } catch (\Exception $exception) {
                    $timeUpdate = null;
                }
                try {
                    $timeLastAccesss = new \DateTime(date((string) $cells[10]->getValue()));
                } catch (\Exception $exception) {
                    $timeLastAccesss = null;
                }

                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams["time_update".$rowNumber] = $timeUpdate ? $timeCreate->format('Y-m-d H:i:s') : null;
                $sqlParams["time_last_access".$rowNumber] = $timeLastAccesss ? $timeLastAccesss->format('Y-m-d H:i:s') : null;

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