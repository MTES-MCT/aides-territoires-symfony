<?php

namespace App\Command\Import\Search;

use App\Command\Import\ImportCommand;
use App\Entity\Organization\OrganizationType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:search_page', description: 'Import search_page')]
class SearchPageImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import search_page';
    protected string $commandTextEnd = '<Import search_page';

    protected function import($input, $output)
    {
        // ==================================================================
        // SEARCH PAGE
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('SEARCH PAGE');

        // fichier
        $filePath = $this->findCsvFile('search_searchpage_');
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
        
        $sqlBase = "INSERT INTO `search_page`
                    (
                    `id`,
                    administrator_id,
                    `name`,
                    slug,
                    meta_title,
                    meta_description,
                    `description`,
                    search_querystring,
                    color1,
                    color2,
                    color3,
                    logo,
                    color4,
                    logo_link,
                    color5,
                    more_content,
                    meta_image,
                    show_audience_field,
                    show_categories_field,
                    show_perimeter_field,
                    show_mobilization_step_field,
                    short_title,
                    time_create,
                    date_create,
                    show_aid_type_field,
                    time_update,
                    show_backers_field,
                    tab_title,
                    show_text_field,
                    contact_link,
                    subdomain_enabled
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
                    :administrator_id".$rowNumber.",
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :meta_title".$rowNumber.",
                    :meta_description".$rowNumber.",
                    :description".$rowNumber.",
                    :search_querystring".$rowNumber.",
                    :color1".$rowNumber.",
                    :color2".$rowNumber.",
                    :color3".$rowNumber.",
                    :logo".$rowNumber.",
                    :color4".$rowNumber.",
                    :logo_link".$rowNumber.",
                    :color5".$rowNumber.",
                    :more_content".$rowNumber.",
                    :meta_image".$rowNumber.",
                    :show_audience_field".$rowNumber.",
                    :show_categories_field".$rowNumber.",
                    :show_perimeter_field".$rowNumber.",
                    :show_mobilization_step_field".$rowNumber.",
                    :short_title".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :show_aid_type_field".$rowNumber.",
                    :time_update".$rowNumber.",
                    :show_backers_field".$rowNumber.",
                    :tab_title".$rowNumber.",
                    :show_text_field".$rowNumber.",
                    :contact_link".$rowNumber.",
                    :subdomain_enabled".$rowNumber."
                ),";


                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["meta_title".$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams["slug".$rowNumber] = $this->stringOrNull((string) $cells[3]->getValue());
                $sqlParams["meta_description".$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams["description".$rowNumber] = $this->stringOrNull((string) $cells[5]->getValue());
                $sqlParams["search_querystring".$rowNumber] = $this->stringOrNull((string) $cells[6]->getValue());
                $sqlParams["color1".$rowNumber] = $this->stringOrNull((string) $cells[7]->getValue());
                $sqlParams["color2".$rowNumber] = $this->stringOrNull((string) $cells[8]->getValue());
                $sqlParams["color3".$rowNumber] = $this->stringOrNull((string) $cells[9]->getValue());
                $sqlParams["logo".$rowNumber] = $this->stringOrNull((string) $cells[10]->getValue());
                $sqlParams["color4".$rowNumber] = $this->stringOrNull((string) $cells[11]->getValue());
                $sqlParams["logo_link".$rowNumber] = $this->stringOrNull((string) $cells[12]->getValue());
                $sqlParams["color5".$rowNumber] = $this->stringOrNull((string) $cells[13]->getValue());
                $sqlParams["more_content".$rowNumber] = $this->stringOrNull((string) $cells[14]->getValue());
                $sqlParams["meta_image".$rowNumber] = $this->stringOrNull((string) $cells[15]->getValue());
                $sqlParams["show_audience_field".$rowNumber] = $this->stringToBool((string) $cells[16]->getValue());
                $sqlParams["show_categories_field".$rowNumber] = $this->stringToBool((string) $cells[17]->getValue());
                $sqlParams["show_perimeter_field".$rowNumber] = $this->stringToBool((string) $cells[18]->getValue());
                $sqlParams["show_mobilization_step_field".$rowNumber] = $this->stringToBool((string) $cells[19]->getValue());
                $sqlParams["short_title".$rowNumber] = $this->stringOrNull((string) $cells[21]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[22]->getValue());
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams["date_create".$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams["show_aid_type_field".$rowNumber] = $this->stringToBool((string) $cells[23]->getValue());
                $timeUpdate = $this->stringToDateTimeOrNull((string) $cells[24]->getValue());
                $sqlParams["time_update".$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;
                $sqlParams["show_backers_field".$rowNumber] = $this->stringToBool((string) $cells[25]->getValue());
                $sqlParams["administrator_id".$rowNumber] = $this->intOrNull((string) $cells[26]->getValue());
                $sqlParams["tab_title".$rowNumber] = $this->stringOrNull((string) $cells[27]->getValue());
                $sqlParams["show_text_field".$rowNumber] = $this->stringToBool((string) $cells[28]->getValue());
                $sqlParams["contact_link".$rowNumber] = $this->stringOrNull((string) $cells[29]->getValue());
                $sqlParams["subdomain_enabled".$rowNumber] = $this->stringToBool((string) $cells[30]->getValue());

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
        // ORGANIZATION TYPES
        // ==================================================================

        $organizationTypesBySlug = [];
        $organizationTypes = $this->managerRegistry->getRepository(OrganizationType::class)->findAll();
        foreach ($organizationTypes as $organizationType) {
            $organizationTypesBySlug[$organizationType->getSlug()] = $organizationType;
        }
        unset($organizationTypes);



        // ==================================================================
        // SEARCH PAGE LIAISON ORGANIZATIONTYPE
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('SEARCH PAGE LIAISON ORGANIZATIONTYPE');

        // fichier
        $filePath = $this->findCsvFile('search_searchpage_');
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

        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                $slugs = $this->stringToArrayOrNull((string) $cells[20]->getValue());

                if (is_array($slugs)) {
                    foreach ($slugs as $slug) {
                        $sqlParams = [];
                        if ($slug && trim($slug) !== '' && isset($organizationTypesBySlug[$slug])) {

                            $sql = "
                            INSERT INTO `search_page_organization_type`
                            (
                                search_page_id,
                                organization_type_id
                            )
                            VALUES 
                            (
                                :search_page_id,
                                :organization_type_id
                            )";

                            

                            $sqlParams["search_page_id"] = (int) $cells[0]->getValue();
                            $sqlParams["organization_type_id"] = (int) $organizationTypesBySlug[$slug]->getId();

                            try {
                                $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                                $stmt->execute($sqlParams);
                            }
                            catch (\Exception $e) {
                                dump($e->getMessage());
                                dump($sql);
                                dump($sqlParams);
                                exit;
                            }
                        }
                    }
                }

                // progressbar
                $io->progressAdvance();

            }
        }

        // libere memoire
        unset($organizationTypesBySlug);

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // SEARCH PAGE CATEGORY
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('SEARCH CATEGORY');

        // fichier
        $filePath = $this->findCsvFile('search_searchpage_available_categories_');
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
        
        $sqlBase = "INSERT INTO `search_page_category`
                    (
                    search_page_id,
                    category_id
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
                    :search_page_id".$rowNumber.",
                    :category_id".$rowNumber."
                ),";


                $sqlParams["search_page_id".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["category_id".$rowNumber] = (string) $cells[2]->getValue();
                
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
        // SEARCH PAGE EXCLUDED AIDS
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('SEARCH EXCLUDED AIDS');

        // fichier
        $filePath = $this->findCsvFile('search_searchpage_excluded_aids_');
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
        
        $sqlBase = "INSERT INTO `search_aid_excluded`
                    (
                    search_page_id,
                    aid_id
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
                    :search_page_id".$rowNumber.",
                    :aid_id".$rowNumber."
                ),";


                $sqlParams["search_page_id".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["aid_id".$rowNumber] = (string) $cells[2]->getValue();
                
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
        // SEARCH PAGE HIGHLIGHTED AIDS
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('SEARCH HIGHLIGHTED AIDS');

        // fichier
        $filePath = $this->findCsvFile('search_searchpage_highlighted_aids_');
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
        
        $sqlBase = "INSERT INTO `search_aid_highlighted`
                    (
                    search_page_id,
                    aid_id
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
                    :search_page_id".$rowNumber.",
                    :aid_id".$rowNumber."
                ),";


                $sqlParams["search_page_id".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["aid_id".$rowNumber] = (string) $cells[2]->getValue();
                
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