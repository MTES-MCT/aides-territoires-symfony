<?php

namespace App\Command\Import\Perimeter;

use App\Command\Import\ImportCommand;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterData;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'at:import:perimeter', description: 'Import perimeter')]
class PerimeterImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import perimeter';
    protected string $commandTextEnd = '<Import perimeter';

    protected function import($input, $output)
    {
        // ==================================================================
        // Périmètres
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('Périmètres');

        // fichier
        $filePath = $this->findCsvFile('geofr_perimeter_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }

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
        
        $sqlBase = "INSERT INTO perimeter
        (
            id,
            scale,
            code,
            name,
            epci,
            zipcodes,
            continent,
            country,
            is_overseas,
            departments,
            regions,
            basin,
            manually_created,
            is_visible_to_users,
            unaccented_name,
            time_create,
            date_create,
            time_update,
            backers_count,
            programs_count,
            categories_count,
            live_aids_count,
            time_obsolete,
            is_obsolete,
            population,
            latitude,
            longitude,
            projects_count,
            density_typology,
            insee,
            siren,
            siret,
            surface
        )
        VALUES
        ";
        $sql = $sqlBase;
        $sqlParams = [];

        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);

        // importe les lignes
        ini_set('auto_detect_line_endings',TRUE);
        $rowNumber = 1;
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 4096, ';')) !== false) {
                if ($rowNumber == 1) {
                    $rowNumber++;
                    continue;
                }

                $rowNumber++;

                $sql .= "
                (
                    :id".$rowNumber.",
                    :scale".$rowNumber.",
                    :code".$rowNumber.",
                    :name".$rowNumber.",
                    :epci".$rowNumber.",
                    :zipcodes".$rowNumber.",
                    :continent".$rowNumber.",
                    :country".$rowNumber.",
                    :is_overseas".$rowNumber.",
                    :departments".$rowNumber.",
                    :regions".$rowNumber.",
                    :basin".$rowNumber.",
                    :manually_created".$rowNumber.",
                    :is_visible_to_users".$rowNumber.",
                    :unaccented_name".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :time_update".$rowNumber.",
                    :backers_count".$rowNumber.",
                    :programs_count".$rowNumber.",
                    :categories_count".$rowNumber.",
                    :live_aids_count".$rowNumber.",
                    :time_obsolete".$rowNumber.",
                    :is_obsolete".$rowNumber.",
                    :population".$rowNumber.",
                    :latitude".$rowNumber.",
                    :longitude".$rowNumber.",
                    :projects_count".$rowNumber.",
                    :density_typology".$rowNumber.",
                    :insee".$rowNumber.",
                    :siren".$rowNumber.",
                    :siret".$rowNumber.",
                    :surface".$rowNumber."
                ),";

                $sqlParams['id'.$rowNumber] = $this->intOrNull((string) $data[0]);
                $sqlParams['scale'.$rowNumber] = $this->intOrNull((string) $data[1]);
                $sqlParams['code'.$rowNumber] = $this->stringOrNull((string) $data[2]);
                $sqlParams['name'.$rowNumber] = $this->stringOrNull((string) $data[3]);
                $sqlParams['epci'.$rowNumber] = $this->stringOrNull((string) $data[4]);
                $sqlParams['zipcodes'.$rowNumber] = $this->stringToJsonOrNull((string) $data[5]);
                $sqlParams['continent'.$rowNumber] = $this->stringOrNull((string) $data[6]);
                $sqlParams['country'.$rowNumber] = $this->stringOrNull((string) $data[7]);
                $sqlParams['is_overseas'.$rowNumber] = $this->stringToBool((string) $data[8]);
                $sqlParams['departments'.$rowNumber] = $this->stringToJsonOrNull((string) $data[9]);
                $sqlParams['regions'.$rowNumber] = $this->stringToJsonOrNull((string) $data[10]);
                $sqlParams['basin'.$rowNumber] = $this->stringOrNull((string) $data[11]);
                $sqlParams['manually_created'.$rowNumber] = $this->stringToBool((string) $data[12]);
                $sqlParams['is_visible_to_users'.$rowNumber] = $this->stringToBool((string) $data[13]);
                $sqlParams['unaccented_name'.$rowNumber] = $this->stringOrNull((string) $data[14]);
                $timeCreate = $this->stringToDateTimeOrNow((string) $data[15]);
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $timeUpdate = $this->stringToDateTimeOrNull((string) $data[16]);
                $sqlParams['time_update'.$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : NULL;
                $sqlParams['backers_count'.$rowNumber] = $this->intOrNull((string) $data[17]);
                $sqlParams['programs_count'.$rowNumber] = $this->intOrNull((string) $data[18]);
                $sqlParams['categories_count'.$rowNumber] = $this->intOrNull((string) $data[19]);
                $sqlParams['live_aids_count'.$rowNumber] = $this->intOrNull((string) $data[20]);
                $timeObsolete = $this->stringToDateTimeOrNull((string) $data[21]);
                $sqlParams['time_obsolete'.$rowNumber] = $timeObsolete ? $timeObsolete->format('Y-m-d H:i:s') : NULL;
                $sqlParams['is_obsolete'.$rowNumber] = $this->stringToBool((string) $data[22]);
                $sqlParams['population'.$rowNumber] = $this->intOrNull((string) $data[23]);
                $sqlParams['latitude'.$rowNumber] = $this->floatOrNull((string) $data[24]);
                $sqlParams['longitude'.$rowNumber] = $this->floatOrNull((string) $data[25]);
                $sqlParams['projects_count'.$rowNumber] = $this->intOrNull((string) $data[26]);
                $sqlParams['density_typology'.$rowNumber] = $this->stringOrNull((string) $data[27]);
                $sqlParams['insee'.$rowNumber] = $this->stringOrNull((string) $data[28]);
                $sqlParams['siren'.$rowNumber] = $this->stringOrNull((string) $data[29]);
                $sqlParams['siret'.$rowNumber] = $this->stringOrNull((string) $data[30]);
                $sqlParams['surface'.$rowNumber] = $this->intOrNull((string) $data[31]);

                if ($rowNumber % $nbByBatch == 0) {
                    try {
                        $sql = substr($sql, 0, -1);

                        $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                        $stmt->execute($sqlParams);
                        
                        $sqlParams = [];
                        $sql = $sqlBase;
                        
                        // advances the progress bar 1 unit
                        $io->progressAdvance();
                    } catch (\Exception $e) {
                        dd($e, $sql, $sqlParams);
                    }
                }
            }

            fclose($handle);
        }

        try {
            // sauvegarde
            if (count($sqlParams) > 0) {
                $sql = substr($sql, 0, -1);
                $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                $stmt->execute($sqlParams);
            }
        } catch (\Exception $e) {
            dd($e, $sql, $sqlParams);
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // Tableau par id pour la suite
        // ==================================================================

        // met tous les périmètres dans un tableau par id
        $perimeters = $this->managerRegistry->getRepository(Perimeter::class)->findAll();
        $perimetersById = [];
        foreach ($perimeters as $perimeter) {
            $perimetersById[$perimeter->getId()] = $perimeter;
        }
        unset($perimeters);
        
        // ==================================================================
        // Périmètres contained in
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('Périmètres contained in');

        // fichier
        $filePath = $this->findCsvFile('geofr_perimeter_contained_in_');
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
        $nbByBatch = 50000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO perimeter_perimeter (perimeter_source, perimeter_target) VALUES ";
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
                $sql .= "(:perimeter_source".$rowNumber.", :perimeter_target".$rowNumber."),";

                $sqlParams["perimeter_source".$rowNumber] = (int) $cells[1]->getValue();
                $sqlParams["perimeter_target".$rowNumber] = (int) $cells[2]->getValue();
                // if (isset($perimetersById[(int) $cells[1]->getValue()]) && isset($perimetersById[(int) $cells[2]->getValue()])) {
                //     $perimetersById[(int) $cells[1]->getValue()]->addPerimetersTo($perimetersById[(int) $cells[2]->getValue()]);

                //     // sauvegarde
                //     $this->managerRegistry->getManager()->persist($perimetersById[(int) $cells[1]->getValue()]);
                // }

                if ($rowNumber % $nbByBatch == 0) {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);

                    $sqlParams = [];
                    $sql = $sqlBase;

                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                }

                // advances the progress bar 1 unit
                // $io->progressAdvance();
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
        // Périmètres data
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('Périmètres data');

        // fichier
        $filePath = $this->findCsvFile('geofr_perimeterdata_');
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
        $nbByBatch = 30000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO perimeter_data (perimeter_id, prop, `value`, time_create, time_update) VALUES ";
        $sql = $sqlBase;
        $sqlParams = [];

        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                // entite
                if (isset($perimetersById[(int) $cells[5]->getValue()])) {

                    $sql .= "(:perimeter_id".$rowNumber.", :prop".$rowNumber.", :value".$rowNumber.", :time_create".$rowNumber.", :time_update".$rowNumber."),";

                    $sqlParams["perimeter_id".$rowNumber] = (int) $cells[5]->getValue();
                    $sqlParams["prop".$rowNumber] = (string) $cells[1]->getValue();
                    $sqlParams["value".$rowNumber] = (string) $cells[2]->getValue();
                    try {
                        $timeCreate = new \DateTime(date((string) $cells[3]->getValue()));
                    } catch (\Exception $exception) {
                        $timeCreate = new \DateTime(date('Y-m-d H:i:s'));
                    }
                    try {
                        $timeUpdate = new \DateTime(date((string) $cells[4]->getValue()));
                    } catch (\Exception $exception) {
                        $timeUpdate = NULL;
                    }
                    $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                    $sqlParams["time_update".$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : NULL;


                    if ($rowNumber % $nbByBatch == 0) {
                        $sql = substr($sql, 0, -1);
    
                        $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                        $stmt->execute($sqlParams);
    
                        $sqlParams = [];
                        $sql = $sqlBase;
    
                        // advances the progress bar 1 unit
                        $io->progressAdvance();
    
                        $this->managerRegistry->getManager()->flush();
                    }
                }
            }
        }

        if (count($sqlParams) > 0) {
            $sql = substr($sql, 0, -1);
            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
            $stmt->execute($sqlParams);
        }

        // $this->managerRegistry->getManager()->flush();

        // ensures that the progress bar is at 100%
        $io->progressFinish();


        // ==================================================================
        // libère mémoire
        // ==================================================================

        unset($perimetersById);
        unset($sqlParams);
        unset($sql);

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}