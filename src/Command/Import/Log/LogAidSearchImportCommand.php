<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use App\Entity\Organization\OrganizationType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:log_aid_search', description: 'Import log aid search')]
class LogAidSearchImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log aid search';
    protected string $commandTextEnd = '<Import log aid search';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG AID SEARCH
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG AID SEARCH');

        // fichier
        $filePath = $this->findCsvFile('stats_aidsearchevent_');
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

        $sqlBase = "INSERT INTO `log_aid_search`
        (
        perimeter_id,
        organization_id,
        user_id,
        querystring,
        results_count,
        `source`,
        search,
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
                    :perimeter_id".$rowNumber.",
                    :organization_id".$rowNumber.",
                    :user_id".$rowNumber.",
                    :querystring".$rowNumber.",
                    :results_count".$rowNumber.",
                    :source".$rowNumber.",
                    :search".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber."
                ),";

                $sqlParams['querystring'.$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams['results_count'.$rowNumber] = $this->intOrNull((string) $cells[3]->getValue());
                $sqlParams['source'.$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[5]->getValue());
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams['perimeter_id'.$rowNumber] = $this->intOrNull((string) $cells[6]->getValue());
                $sqlParams['search'.$rowNumber] = $this->stringOrNull((string) $cells[7]->getValue());
                $sqlParams['organization_id'.$rowNumber] = $this->intOrNull((string) $cells[8]->getValue());
                $sqlParams['user_id'.$rowNumber] = $this->intOrNull((string) $cells[9]->getValue());

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
        $reader->close();

        // ==================================================================
        // OrganizationType id by slug
        // ==================================================================

        // $organizationTypeIdBySlugs = [];
        // $organizationTypes = $this->managerRegistry->getRepository(OrganizationType::class)->findAll();
        // foreach ($organizationTypes as $organizationType) {
        //     $organizationTypeIdBySlugs[$organizationType->getSlug()] = $organizationType->getId();
        // }
        // unset($organizationTypes);

        // // ==================================================================
        // // LOG AID SEARCH Liaison OrganizationType
        // // ==================================================================
        // // TODO FAIRE MARCHER CET IMPORT
        // $io = new SymfonyStyle($input, $output);
        // $io->info('LOG AID SEARCH Liaison OrganizationType');

        // // fichier
        // $filePath = $this->findCsvFile('stats_aidsearchevent_');
        // if (!$filePath) {
        //     throw new \Exception('File missing');
        // }
        // $reader = ReaderEntityFactory::createReaderFromFile($filePath);
        // $reader->setFieldDelimiter(';');
        // $reader->setFieldEnclosure('"');

        // // ouverture
        // $reader->open($filePath);

        // // batch
        // $nbByBatch = 1;
        // $nbBatch = ceil($nbToImport / $nbByBatch);

        // // progressbar
        // $io->createProgressBar($nbBatch);

        // // starts and displays the progress bar
        // $io->progressStart();

        // $sqlBase2 = "INSERT INTO log_aid_search_organization_type
        // (
        //     log_aid_search_id,
        //     organization_type_id
        // )
        // VALUES ";
        // $sql2 = $sqlBase2;
        // $sqlParams2 = [];

        // $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);

        // // importe les lignes
        // foreach ($reader->getSheetIterator() as $sheet) {
        //     foreach ($sheet->getRowIterator() as $rowNumber => $row) {
        //         if ($rowNumber == 1) {
        //             continue;
        //         }
        //         // do stuff with the row
        //         $cells = $row->getCells();

        //         $organizationTypeSlugs = $this->stringToArrayOrNull((string) $cells[1]->getValue());
        //         if (is_array($organizationTypeSlugs)) {
        //             $i=1;
        //             foreach ($organizationTypeSlugs as $organizationTypeSlug) {
        //                 if (isset($organizationTypeIdBySlugs[$this->redoSlug($organizationTypeSlug)])) {
        //                     $sql2 .= "
        //                     (
        //                         :log_aid_search_id".$rowNumber."_".$i.",
        //                         :organization_type_id".$rowNumber."_".$i."
        //                     ),";

        //                     $sqlParams2['log_aid_search_id'.$rowNumber."_".$i] = (int) $cells[0]->getValue();
        //                     $sqlParams2['organization_type_id'.$rowNumber."_".$i] = $organizationTypeIdBySlugs[$this->redoSlug($organizationTypeSlug)];
        //                 }
        //                 $i++;
        //             }
        //         }
        //         unset($organizationTypeSlugs);

        //         if ($rowNumber % $nbByBatch == 0) {
        //             try {
                        
        //                 if (count($sqlParams2) > 0) {
        //                     $sql2 = substr($sql2, 0, -1);
    
        //                     $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql2);
        //                     $stmt->execute($sqlParams2);
        //                 }

        //                 $sqlParams2 = [];
        //                 $sql2 = $sqlBase2;
                        
    
    
        //                 // advances the progress bar 1 unit
        //                 $io->progressAdvance();
        //             } catch (\Exception $e) {
        //             }

        //         }
        //     }
        // }

        // try {
        //     // sauvegarde
        //     if (count($sqlParams2) > 0) {
        //         $sql2 = substr($sql2, 0, -1);
        //         $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql2);
        //         $stmt->execute($sqlParams2);
        //     }
        // } catch (\Exception $e) {
        // }
        // // ensures that the progress bar is at 100%
        // $io->progressFinish();
        // $reader->close();
        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}