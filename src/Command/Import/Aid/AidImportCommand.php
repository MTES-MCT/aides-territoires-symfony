<?php

namespace App\Command\Import\Aid;

use App\Command\Import\ImportCommand;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\DataSource\DataSource;
use App\Entity\Eligibility\EligibilityTest;
use App\Entity\Keyword\Keyword;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\User\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:aid', description: 'Import aid')]
class AidImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import aid';
    protected string $commandTextEnd = '<Import aid';

    protected function import($input, $output)
    {
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
        // DATA SOURCE
        // ==================================================================

        $dataSourcesById = [];
        $dataSources = $this->managerRegistry->getRepository(DataSource::class)->findAll();
        foreach ($dataSources as $dataSource) {
            $dataSourcesById[$dataSource->getId()] = $dataSource;
        }
        unset($dataSources);

        // ==================================================================
        // ELIGIBILITY TESTS
        // ==================================================================

        $eligibilityTestsById = [];
        $eligibilityTests = $this->managerRegistry->getRepository(EligibilityTest::class)->findAll();
        foreach ($eligibilityTests as $eligibilityTest) {
            $eligibilityTestsById[$eligibilityTest->getId()] = $eligibilityTest;
        }
        unset($eligibilityTests);

        // ==================================================================
        // AID TYPE GROUP
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID TYPE GROUP');
        
        $aidTypeGroupsBySlug = [];
        $items = [
            ['slug' => 'financial_group', 'name' => 'Aide financière'],
            ['slug' => 'technical_group', 'name' => 'Aide en ingénierie'],
        ];
        $i=0;
        foreach ($items as $item) {
            $aidTypeGroup = new AidTypeGroup();
            $aidTypeGroup->setSlug($item['slug']);
            $aidTypeGroup->setName($item['name']);
            $aidTypeGroup->setPosition($i);
            $i++;
            $this->managerRegistry->getManager()->persist($aidTypeGroup);
            $this->managerRegistry->getManager()->flush();
            $aidTypeGroupsBySlug[$item['slug']] = $aidTypeGroup;
        }

        // ==================================================================
        // AID TYPE
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID TYPE');

        $aidTypesBySlug = [];
        $items = [
            ['slug' => 'grant', 'name' => 'Subvention', 'groupSlug' => 'financial_group'],
            ['slug' => 'loan', 'name' => 'Prêt', 'groupSlug' => 'financial_group'],
            ['slug' => 'recoverable_advance', 'name' => 'Avance récupérable', 'groupSlug' => 'financial_group'],
            ['slug' => 'cee', 'name' => 'Certificat d\'économie d\'énergie (CEE)', 'groupSlug' => 'financial_group'],
            ['slug' => 'other', 'name' => 'Autre aide financière', 'groupSlug' => 'financial_group'],

            ['slug' => 'technical_engineering', 'name' => 'Ingénierie technique', 'groupSlug' => 'technical_group'],
            ['slug' => 'financial_engineering', 'name' => 'Ingénierie financière', 'groupSlug' => 'technical_group'],
            ['slug' => 'legal_engineering', 'name' => 'Ingénierie Juridique / administrative', 'groupSlug' => 'technical_group'],
        ];
        $i=0;
        foreach ($items as $item) {
            $aidType = new AidType();
            $aidType->setSlug($item['slug']);
            $aidType->setName($item['name']);
            $aidType->setAidTypeGroup($aidTypeGroupsBySlug[$item['groupSlug']]);
            $aidType->setPosition($i);
            $i++;
            $this->managerRegistry->getManager()->persist($aidType);
            $this->managerRegistry->getManager()->flush();
            $aidTypesBySlug[$aidType->getslug()] = $aidType;
        }

        // ==================================================================
        // AID DESTINATION
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID DESTINATION');

        $aidDestinationsBySlug = [];
        $items = [
            ['slug' => 'supply', 'name' => 'Dépenses de fonctionnement'],
            ['slug' => 'investment', 'name' => 'Dépenses d’investissement'],
        ];
        $i=0;
        foreach ($items as $item) {
            $aidDestination = new AidDestination();
            $aidDestination->setSlug($item['slug']);
            $aidDestination->setName($item['name']);
            $aidDestination->setPosition($i);
            $i++;
            $this->managerRegistry->getManager()->persist($aidDestination);
            $this->managerRegistry->getManager()->flush();
            $aidDestinationsBySlug[$aidDestination->getslug()] = $aidDestination;
        }

        // ==================================================================
        // AID STEPS
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID STEPS');

        $aidStepBySlug = [];
        $items = [
            ['slug' => 'preop', 'name' => 'Réflexion / conception'],
            ['slug' => 'op', 'name' => 'Mise en œuvre / réalisation'],
            ['slug' => 'postop', 'name' => 'Usage / valorisation'],
        ];
        $i=0;
        foreach ($items as $item) {
            $aidStep = new AidStep();
            $aidStep->setSlug($item['slug']);
            $aidStep->setName($item['name']);
            $aidStep->setPosition($i);
            $i++;
            $this->managerRegistry->getManager()->persist($aidStep);
            $this->managerRegistry->getManager()->flush();
            $aidStepBySlug[$aidStep->getslug()] = $aidStep;
        }

        // ==================================================================
        // AID RECURRENCES
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID RECURRENCES');

        $aidRecurrencesBySlug = [];
        $items = [
            ['slug' => 'oneoff', 'name' => 'Ponctuelle'],
            ['slug' => 'ongoing', 'name' => 'Permanente'],
            ['slug' => 'recurring', 'name' => 'Récurrente'],
        ];
        $i=0;
        foreach ($items as $item) {
            $aidRecurrence = new AidRecurrence();
            $aidRecurrence->setSlug($item['slug']);
            $aidRecurrence->setName($item['name']);
            $aidRecurrence->setPosition($i);
            $i++;
            $this->managerRegistry->getManager()->persist($aidRecurrence);
            $this->managerRegistry->getManager()->flush();
            $aidRecurrencesBySlug[$aidRecurrence->getslug()] = $aidRecurrence;
        }

        // ==================================================================
        // Tableau par id pour la suite
        // ==================================================================

        // met tous les périmètres dans un tableau par id
        $perimeters = $this->managerRegistry->getRepository(Perimeter::class)->findAll();
        $perimetersById = [];
        foreach ($perimeters as $perimeter) {
            $perimetersById[$perimeter->getId()] = $perimeter;
        }

        // ==================================================================
        // AID
        // ==================================================================


        $io = new SymfonyStyle($input, $output);
        $io->info('Aides');

        // fichier
        $filePath = $this->findCsvFile('aids_aid_');
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
        
        $sqlBase = "INSERT INTO `aid`
        (
        `id`,
        `name`,
        `description`,
        `status`,
        origin_url,
        date_start,
        date_predeposit,
        date_submission_deadline,
        contact_email,
        contact_phone,
        contact_detail,
        time_create,
        eligibility,
        perimeter_id,
        application_url,
        time_update,
        slug,
        is_imported,
        import_uniqueid,
        financer_suggestion,
        import_data_url,
        date_import_last_access,
        import_share_licence,
        is_call_for_project,
        is_amendment,
        amendment_author_name,
        amendment_comment,
        amendment_author_email,
        amendment_author_org,
        subvention_rate_min,
        subvention_rate_max,
        subvention_comment,

        contact,
        instructor_suggestion,
        project_examples,
        perimeter_suggestion,
        short_title,
        in_france_relance,
        local_characteristics,
        import_data_source_id,
        is_generic,
        import_raw_object,
        loan_amount,

        other_financial_aid_comment,
        recoverable_advance_amount,
        name_initial,
        author_notification,
        import_raw_object_calendar,
        import_raw_object_temp,
        import_raw_object_temp_calendar,
        european_aid,
        import_data_mention,
        has_broken_link,

        is_charged,
        import_updated,
        ds_id,
        ds_mapping,
        ds_schema_exists,
        contact_info_updated,
        date_create,
        time_published,
        date_published,

        author_id,
        aid_recurrence_id
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
                    :description".$rowNumber.",
                    :status".$rowNumber.",
                    :origin_url".$rowNumber.",
                    :date_start".$rowNumber.",
                    :date_predeposit".$rowNumber.",
                    :date_submission_deadline".$rowNumber.",
                    :contact_email".$rowNumber.",
                    :contact_phone".$rowNumber.",
                    :contact_detail".$rowNumber.",
                    :time_create".$rowNumber.",
                    :eligibility".$rowNumber.",
                    :perimeter_id".$rowNumber.",
                    :application_url".$rowNumber.",
                    :time_update".$rowNumber.",
                    :slug".$rowNumber.",
                    :is_imported".$rowNumber.",
                    :import_uniqueid".$rowNumber.",
                    :financer_suggestion".$rowNumber.",
                    :import_data_url".$rowNumber.",
                    :date_import_last_access".$rowNumber.",

                    :import_share_licence".$rowNumber.",
                    :is_call_for_project".$rowNumber.",
                    :is_amendment".$rowNumber.",
                    :amendment_author_name".$rowNumber.",
                    :amendment_comment".$rowNumber.",
                    :amendment_author_email".$rowNumber.",
                    :amendment_author_org".$rowNumber.",
                    :subvention_rate_min".$rowNumber.",
                    :subvention_rate_max".$rowNumber.",
                    :subvention_comment".$rowNumber.",

                    :contact".$rowNumber.",
                    :instructor_suggestion".$rowNumber.",
                    :project_examples".$rowNumber.",
                    :perimeter_suggestion".$rowNumber.",
                    :short_title".$rowNumber.",
                    :in_france_relance".$rowNumber.",
                    :local_characteristics".$rowNumber.",
                    :import_data_source_id".$rowNumber.",
                    :is_generic".$rowNumber.",
                    :import_raw_object".$rowNumber.",
                    :loan_amount".$rowNumber.",

                    :other_financial_aid_comment".$rowNumber.",
                    :recoverable_advance_amount".$rowNumber.",
                    :name_initial".$rowNumber.",
                    :author_notification".$rowNumber.",
                    :import_raw_object_calendar".$rowNumber.",
                    :import_raw_object_temp".$rowNumber.",
                    :import_raw_object_temp_calendar".$rowNumber.",
                    :european_aid".$rowNumber.",
                    :import_data_mention".$rowNumber.",
                    :has_broken_link".$rowNumber.",
                    
                    :is_charged".$rowNumber.",
                    :import_updated".$rowNumber.",
                    :ds_id".$rowNumber.",
                    :ds_mapping".$rowNumber.",
                    :ds_schema_exists".$rowNumber.",
                    :contact_info_updated".$rowNumber.",
                    :date_create".$rowNumber.",
                    :time_published".$rowNumber.",   
                    :date_published".$rowNumber.",   

                    :author_id".$rowNumber.",   
                    :aid_recurrence_id".$rowNumber."   
                ),";

                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams['name'.$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams['description'.$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams['status'.$rowNumber] = $this->stringOrNull((string) $cells[3]->getValue());
                $sqlParams['origin_url'.$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $dateStart = $this->stringToDateTimeOrNull((string) $cells[8]->getValue());
                $sqlParams['date_start'.$rowNumber] = $dateStart ? $dateStart->format('Y-m-d') : null;
                $datePredeposit = $this->stringToDateTimeOrNull((string) $cells[9]->getValue());
                $sqlParams['date_predeposit'.$rowNumber] = $datePredeposit ? $datePredeposit->format('Y-m-d') : null;
                $dateSubmissionDeadline = $this->stringToDateTimeOrNull((string) $cells[10]->getValue());
                $sqlParams['date_submission_deadline'.$rowNumber] = $dateSubmissionDeadline ? $dateSubmissionDeadline->format('Y-m-d') : null;
                $sqlParams['contact_email'.$rowNumber] = $this->stringOrNull((string) $cells[11]->getValue());
                $sqlParams['contact_phone'.$rowNumber] = $this->stringOrNull((string) $cells[12]->getValue());
                $sqlParams['contact_detail'.$rowNumber] = $this->stringOrNull((string) $cells[13]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[14]->getValue());
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                
                $sqlParams['eligibility'.$rowNumber] = $this->stringOrNull((string) $cells[17]->getValue());
                $sqlParams['perimeter_id'.$rowNumber] = $this->intOrNull((string) $cells[19]->getValue());
                $sqlParams['application_url'.$rowNumber] = $this->stringOrNull((string) $cells[20]->getValue());
                $timeUpdate = $this->stringToDateTimeOrNull((string) $cells[21]->getValue());
                $sqlParams['time_update'.$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;
                $sqlParams['slug'.$rowNumber] = (string) $cells[22]->getValue();
                $sqlParams['is_imported'.$rowNumber] = $this->stringToBool((string) $cells[23]->getValue());
                $sqlParams['import_uniqueid'.$rowNumber] = $this->stringOrNull((string) $cells[24]->getValue());
                $sqlParams['financer_suggestion'.$rowNumber] = $this->stringOrNull((string) $cells[25]->getValue());
                $sqlParams['import_data_url'.$rowNumber] = $this->stringOrNull((string) $cells[26]->getValue());
                $dateImportLastAccess = $this->stringToDateTimeOrNull((string) $cells[27]->getValue());
                $sqlParams['date_import_last_access'.$rowNumber] = $dateImportLastAccess ? $dateImportLastAccess->format('Y-m-d') : null;
                
                $sqlParams['import_share_licence'.$rowNumber] = $this->stringOrNull((string) $cells[28]->getValue());
                $sqlParams['is_call_for_project'.$rowNumber] = $this->stringToBool((string) $cells[29]->getValue());
                $sqlParams['is_amendment'.$rowNumber] = $this->stringToBool((string) $cells[32]->getValue());
                $sqlParams['amendment_author_name'.$rowNumber] = $this->stringOrNull((string) $cells[33]->getValue());
                $sqlParams['amendment_comment'.$rowNumber] = $this->stringOrNull((string) $cells[34]->getValue());
                $sqlParams['amendment_author_email'.$rowNumber] = $this->stringOrNull((string) $cells[35]->getValue());
                $sqlParams['amendment_author_org'.$rowNumber] = $this->stringOrNull((string) $cells[36]->getValue());
                $range = $this->intrangeToArray((string) $cells[37]->getValue());
                $sqlParams['subvention_rate_min'.$rowNumber] = $range['min'];
                $sqlParams['subvention_rate_max'.$rowNumber] = $range['max'];
                $sqlParams['subvention_comment'.$rowNumber] = $this->stringOrNull((string) $cells[38]->getValue());

                $sqlParams['contact'.$rowNumber] = $this->stringOrNull((string) $cells[39]->getValue());
                $sqlParams['instructor_suggestion'.$rowNumber] = $this->stringOrNull((string) $cells[40]->getValue());
                $sqlParams['project_examples'.$rowNumber] = $this->stringOrNull((string) $cells[41]->getValue());
                $sqlParams['perimeter_suggestion'.$rowNumber] = $this->stringOrNull((string) $cells[42]->getValue());
                $sqlParams['short_title'.$rowNumber] = $this->stringOrNull((string) $cells[43]->getValue());
                $sqlParams['in_france_relance'.$rowNumber] = $this->stringToBool((string) $cells[44]->getValue());
                $sqlParams['local_characteristics'.$rowNumber] = $this->stringOrNull((string) $cells[46]->getValue());
                $sqlParams['import_data_source_id'.$rowNumber] = $this->intOrNull((string) $cells[47]->getValue());
                $sqlParams['is_generic'.$rowNumber] = $this->stringToBool((string) $cells[49]->getValue());
                $sqlParams['import_raw_object'.$rowNumber] = $this->stringOrNull((string) $cells[50]->getValue());
                $sqlParams['loan_amount'.$rowNumber] = $this->intOrNull((string) $cells[51]->getValue());

                $sqlParams['other_financial_aid_comment'.$rowNumber] = $this->stringOrNull((string) $cells[52]->getValue());
                $sqlParams['recoverable_advance_amount'.$rowNumber] = $this->intOrNull((string) $cells[53]->getValue());
                $sqlParams['name_initial'.$rowNumber] = $this->stringOrNull((string) $cells[55]->getValue());
                $sqlParams['author_notification'.$rowNumber] = $this->stringToBool((string) $cells[56]->getValue());
                $sqlParams['import_raw_object_calendar'.$rowNumber] = $this->stringOrNull((string) $cells[57]->getValue());
                $sqlParams['import_raw_object_temp'.$rowNumber] = $this->stringOrNull((string) $cells[58]->getValue());
                $sqlParams['import_raw_object_temp_calendar'.$rowNumber] = $this->stringOrNull((string) $cells[59]->getValue());
                $sqlParams['european_aid'.$rowNumber] = $this->stringOrNull((string) $cells[60]->getValue());
                $sqlParams['import_data_mention'.$rowNumber] = $this->stringOrNull((string) $cells[61]->getValue());
                $sqlParams['has_broken_link'.$rowNumber] = $this->stringToBool((string) $cells[62]->getValue());

                $sqlParams['is_charged'.$rowNumber] = $this->stringToBool((string) $cells[63]->getValue());
                $sqlParams['import_updated'.$rowNumber] = $this->stringToBool((string) $cells[64]->getValue());
                $sqlParams['ds_id'.$rowNumber] = $this->intOrNull((string) $cells[65]->getValue());
                $sqlParams['ds_mapping'.$rowNumber] = $this->stringOrNull((string) $cells[66]->getValue());
                $sqlParams['ds_schema_exists'.$rowNumber] = $this->stringToBool((string) $cells[67]->getValue());
                $sqlParams['contact_info_updated'.$rowNumber] = $this->stringToBool((string) $cells[68]->getValue());
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $timePublished = $this->stringToDateTimeOrNull((string) $cells[30]->getValue());
                $sqlParams['time_published'.$rowNumber] = $timePublished ? $timePublished->format('Y-m-d H:i:s') : null;
                $sqlParams['date_published'.$rowNumber] = $timePublished ? $timePublished->format('Y-m-d') : null;

                $sqlParams['author_id'.$rowNumber] = $this->intOrNull($cells[15]->getValue());
                $sqlParams['aid_recurrence_id'.$rowNumber] = isset($aidRecurrencesBySlug[$this->stringOrNull((string) $cells[18]->getValue())]) ? $aidRecurrencesBySlug[$this->stringOrNull((string) $cells[18]->getValue())]->getId() : null;

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
                        dump($e->getMessage());
                        dump($sql);
                        dump($sqlParams);
                        exit;
                    }
                }
            }
        }

        $this->managerRegistry->getManager()->flush();

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // tableau AID
        // ==================================================================

        $aids = $this->managerRegistry->getRepository(Aid::class)->findAll();
        $aidsById = [];
        foreach ($aids as $aid) {
            $aidsById[$aid->getId()] = $aid;
        }
        unset($aids);


        // ==================================================================
        // AID DESTINATION / STEP / TYPE / AUDIENCE (organization_type)
        // ==================================================================


        $io = new SymfonyStyle($input, $output);
        $io->info('AID DESTINATION / STEP / TYPE / AUDIENCE (organization_type)');

        // fichier
        $filePath = $this->findCsvFile('aids_aid_');
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
        
        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                // entite
                $entity = $aidsById[(int) $cells[0]->getValue()];

                $audiences = $this->stringToArrayOrNull((string) $cells[5]->getValue());
                if (is_array($audiences)) {
                    foreach ($audiences as $audience) {
                        if (isset($organizationTypesBySlug[trim($this->redoSlug($audience))])) {
                            $entity->addAidAudience($organizationTypesBySlug[trim($this->redoSlug($audience))]);
                        }
                    }
                }

                $types = $this->stringToArrayOrNull((string) $cells[6]->getValue());
                if (is_array($types)) {
                    foreach ($types as $type) {
                        if (isset($aidTypesBySlug[trim($this->redoSlug($type))])) {
                            $entity->addAidType($aidTypesBySlug[trim($this->redoSlug($type))]);
                        }
                    }
                }

                $destinations = $this->stringToArrayOrNull((string) $cells[7]->getValue());
                if (is_array($destinations)) {
                    foreach ($destinations as $destination) {
                        if (isset($aidDestinationsBySlug[trim($this->redoSlug($destination))])) {
                            $entity->addAidDestination($aidDestinationsBySlug[trim($this->redoSlug($destination))]);
                        }
                    }
                }                

                $steps = $this->stringToArrayOrNull((string) $cells[16]->getValue());
                if (is_array($steps)) {
                    foreach ($steps as $step) {
                        if (isset($aidStepBySlug[trim($this->redoSlug($step))])) {
                            $entity->addAidStep($aidStepBySlug[trim($this->redoSlug($step))]);
                        }
                    }
                }
                

                // sauvegarde
                $this->managerRegistry->getManager()->persist($entity);

                if ($rowNumber % $nbByBatch == 0) {
                    $this->managerRegistry->getManager()->flush();
                }

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        $this->managerRegistry->getManager()->flush();

        // ensures that the progress bar is at 100%
        $io->progressFinish();


        // ==================================================================
        // AID AMENDED & GENERIC AID
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID AMENDED & GENERIC AID');

        // fichier
        $filePath = $this->findCsvFile('aids_aid_');
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
        
        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                // amended
                if (isset($aidsById[(int) $cells[0]->getValue()]) && isset($aidsById[(int) $cells[31]->getValue()])) {
                    $aidsById[(int) $cells[0]->getValue()]->setAmendedAid($aidsById[(int) $cells[31]->getValue()]);
                    $this->managerRegistry->getManager()->persist($aidsById[(int) $cells[0]->getValue()]);
                }

                // generic
                if (isset($aidsById[(int) $cells[0]->getValue()]) && isset($aidsById[(int) $cells[45]->getValue()])) {
                    $aidsById[(int) $cells[0]->getValue()]->setGenericAid($aidsById[(int) $cells[45]->getValue()]);
                    $this->managerRegistry->getManager()->persist($aidsById[(int) $cells[0]->getValue()]);
                }

                // sauvegarde
                $this->managerRegistry->getManager()->persist($entity);

                if ($rowNumber % $nbByBatch == 0) {
                    $this->managerRegistry->getManager()->flush();
                }

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        $this->managerRegistry->getManager()->flush();

        // ==================================================================
        // AID CATEGORY
        // ==================================================================

        $categories = $this->managerRegistry->getRepository(Category::class)->findAll();
        $categoriesById = [];
        foreach ($categories as $category) {
            $categoriesById[$category->getId()] = $category;
        }
        unset($categories);

        $io = new SymfonyStyle($input, $output);
        $io->info('AID CATEGORY');

        // fichier
        $filePath = $this->findCsvFile('aids_aid_categories_');
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
        
        $sqlBase = "INSERT INTO `aid_category`
        (
        aid_id,
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

                if (isset($aidsById[(int) $cells[1]->getValue()]) && isset($categoriesById[(int) $cells[2]->getValue()])) {
                    $sql .= "
                    (
                        :aid_id".$rowNumber.",
                        :category_id".$rowNumber."      
                    ),";
    
                    $sqlParams['aid_id'.$rowNumber] = (int) $cells[1]->getValue();
                    $sqlParams['category_id'.$rowNumber] = (int) $cells[2]->getValue();
                }
                
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

        unset($categoriesById);

        // ==================================================================
        // AID KEYWORD
        // ==================================================================

        $keywords = $this->managerRegistry->getRepository(Keyword::class)->findAll();
        $keywordsById = [];
        foreach ($keywords as $keyword) {
            $keywordsById[$category->getId()] = $keyword;
        }
        unset($keywords);

        $io = new SymfonyStyle($input, $output);
        $io->info('AID KEYWORD');

        // fichier
        $filePath = $this->findCsvFile('aids_aid_keywords_');
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
        
        $sqlBase = "INSERT INTO `aid_keyword`
        (
        aid_id,
        keyword_id
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
                    :aid_id".$rowNumber.",
                    :keyword_id".$rowNumber."      
                ),";

                $sqlParams['aid_id'.$rowNumber] = (int) $cells[1]->getValue();
                $sqlParams['keyword_id'.$rowNumber] = (int) $cells[2]->getValue();
                
                
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

        unset($keywordsById);

        // ==================================================================
        // AID PROGRAMS
        // ==================================================================

        $programs = $this->managerRegistry->getRepository(Program::class)->findAll();
        $programsById = [];
        foreach ($programs as $program) {
            $programsById[$category->getId()] = $program;
        }
        unset($programs);

        $io = new SymfonyStyle($input, $output);
        $io->info('AID PROGRAMS');

        // fichier
        $filePath = $this->findCsvFile('aids_aid_programs_');
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
        
        $sqlBase = "INSERT INTO `aid_program`
        (
        aid_id,
        program_id
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
                    :aid_id".$rowNumber.",
                    :program_id".$rowNumber."      
                ),";

                $sqlParams['aid_id'.$rowNumber] = (int) $cells[1]->getValue();
                $sqlParams['program_id'.$rowNumber] = (int) $cells[2]->getValue();
                
                
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

        unset($programsById);

        // ==================================================================
        // AID FINANCER
        // ==================================================================

        $backers = $this->managerRegistry->getRepository(Backer::class)->findAll();
        $backersById = [];
        foreach ($backers as $backer) {
            $backersById[$backer->getId()] = $backer;
        }
        unset($backers);

        $io = new SymfonyStyle($input, $output);
        $io->info('AID FINANCER');

        // fichier
        $filePath = $this->findCsvFile('aids_aidfinancer_');
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
        
        $sqlBase = "INSERT INTO `aid_financer`
        (
        aid_id,
        backer_id,
        `position`
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
                    :aid_id".$rowNumber.",
                    :backer_id".$rowNumber.",      
                    :position".$rowNumber."      
                ),";

                $sqlParams['aid_id'.$rowNumber] = (int) $cells[1]->getValue();
                $sqlParams['backer_id'.$rowNumber] = (int) $cells[2]->getValue();
                $sqlParams['position'.$rowNumber] = (int) $cells[3]->getValue();
                

                if ($rowNumber % $nbByBatch == 0 && count($sqlParams) > 0) {
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

        // ==================================================================
        // AID INSTRUCTOR
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID INSTRUCTOR');

        // fichier
        $filePath = $this->findCsvFile('aids_aidinstructor_');
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
        
        $sqlBase = "INSERT INTO `aid_instructor`
        (
        aid_id,
        backer_id,
        `position`
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

                if (isset($aidsById[(int) $cells[1]->getValue()]) && isset($backersById[(int) $cells[2]->getValue()])) {
                    $sql .= "
                    (
                        :aid_id".$rowNumber.",
                        :backer_id".$rowNumber.",      
                        :position".$rowNumber."      
                    ),";
    
                    $sqlParams['aid_id'.$rowNumber] = (int) $cells[1]->getValue();
                    $sqlParams['backer_id'.$rowNumber] = (int) $cells[2]->getValue();
                    $sqlParams['position'.$rowNumber] = (int) $cells[3]->getValue();
                }
                
                if ($rowNumber % $nbByBatch == 0 && count($sqlParams) > 0) {
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

        unset($backersById);

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}