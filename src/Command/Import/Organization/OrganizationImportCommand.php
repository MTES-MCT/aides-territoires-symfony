<?php

namespace App\Command\Import\Organization;

use App\Command\Import\ImportCommand;
use App\Entity\Backer\Backer;
use App\Entity\Organization\OrganizationType;
use App\Entity\Organization\OrganizationTypeGroup;
use App\Entity\Perimeter\Perimeter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:organization', description: 'Import organization')]
class OrganizationImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import organization';
    protected string $commandTextEnd = '<Import organization';

    protected function import($input, $output)
    {

        // ==================================================================
        // Types & TypeGroups
        // ==================================================================
        $items = [
            [
                'name' => 'Une collectivité',
                'items' => [
                    ['name' => 'Commune', 'slug' => 'commune'],
                    ['name' => 'Intercommunalité / Pays', 'slug' => 'epci'],
                    ['name' => 'Département', 'slug' => 'department'],
                    ['name' => 'Région', 'slug' => 'region'],
                    ['name' => 'Collectivité d’outre-mer à statut particulier', 'slug' => 'special'],
                ]
            ],
            [
                'name' => 'Un autre bénéficiaire',
                'items' => [
                    ['name' => 'Établissement public', 'slug' => OrganizationType::SLUG_PUBLIC_ORG],
                    ['name' => 'Entreprise publique locale (Sem, Spl, SemOp)', 'slug' => OrganizationType::SLUG_PUBLIC_CIES],
                    ['name' => 'Association', 'slug' => 'association'],
                    ['name' => 'Entreprise privée', 'slug' => OrganizationType::SLUG_PRIVATE_SECTOR],
                    ['name' => 'Particulier', 'slug' => OrganizationType::SLUG_PRIVATE_PERSON],
                    ['name' => 'Agriculteur', 'slug' => 'farmer'],
                    ['name' => 'Recherche', 'slug' => 'researcher'],
                ]
            ],
        ];
        foreach ($items as $item) {
            $organizationTypeGroup = new OrganizationTypeGroup();
            $organizationTypeGroup->setName($item['name']);
            foreach ($item['items'] as $subItem) {
                $organizationType = new OrganizationType();
                $organizationType->setName($subItem['name']);
                $organizationType->setSlug($subItem['slug']);
                $organizationTypeGroup->addOrganizationType($organizationType);
            }
            $this->managerRegistry->getManager()->persist($organizationTypeGroup);
        }
        $this->managerRegistry->getManager()->flush();

        // les types par slug
        $organizationTypes = $this->managerRegistry->getRepository(OrganizationType::class)->findAll();
        $organizationTypesBySlug = [];
        foreach ($organizationTypes as $organizationType) {
            $organizationTypesBySlug[$organizationType->getSlug()] = $organizationType;
        }
        unset($organizationTypes);


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
        // organization
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('organization');

        // fichier
        $filePath = $this->findCsvFile('organizations_organization_');
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
        
        $sqlBase = "INSERT INTO `organization`
                    (
                    `id`, organization_type_id, perimeter_id, perimeter_department_id, perimeter_region_id, backer_id,
                    `name`, slug, `address`, city_name, zip_code,
                    siren_code, siret_code, ape_code,
                    inhabitants_number, voters_number, corporates_number, associations_number,
                    municipal_roads, departmental_roads, tram_roads,
                    lamppost_number, library_number, medialibrary_number, theater_number, museum_number, kindergarten_number,
                    primary_school_number, middle_school_number, high_school_number, university_number,
                    swimming_pool_number, place_of_worship_number, cemetery_number,
                    time_create, date_create, time_update, imported_time, is_imported, intercommunality_type,
                    bridge_number, cinema_number, covered_sporting_complex_number, football_field_number, forest_number,
                    nursery_number, other_outside_structure_number, protected_monument_number, rec_center_number,
                    running_track_number, shops_number, tennis_court_number,
                    density_typology, insee_code, population_strata
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
                    :organization_type_id".$rowNumber.", :perimeter_id".$rowNumber.", :perimeter_department_id".$rowNumber.", :perimeter_region_id".$rowNumber.", :backer_id".$rowNumber.",
                    :name".$rowNumber.", :slug".$rowNumber.", :address".$rowNumber.", :city_name".$rowNumber.", :zip_code".$rowNumber.",
                    :siren_code".$rowNumber.", :siret_code".$rowNumber.", :ape_code".$rowNumber.",
                    :inhabitants_number".$rowNumber.", :voters_number".$rowNumber.", :corporates_number".$rowNumber.", :associations_number".$rowNumber.",
                    :municipal_roads".$rowNumber.", :departmental_roads".$rowNumber.", :tram_roads".$rowNumber.",
                    :lamppost_number".$rowNumber.", :library_number".$rowNumber.", :medialibrary_number".$rowNumber.", :theater_number".$rowNumber.", :museum_number".$rowNumber.", :kindergarten_number".$rowNumber.",
                    :primary_school_number".$rowNumber.", :middle_school_number".$rowNumber.", :high_school_number".$rowNumber.", :university_number".$rowNumber.",
                    :swimming_pool_number".$rowNumber.", :place_of_worship_number".$rowNumber.", :cemetery_number".$rowNumber.",
                    :time_create".$rowNumber.", :date_create".$rowNumber.", :time_update".$rowNumber.", :imported_time".$rowNumber.", :is_imported".$rowNumber.", :intercommunality_type".$rowNumber.",
                    :bridge_number".$rowNumber.", :cinema_number".$rowNumber.", :covered_sporting_complex_number".$rowNumber.", :football_field_number".$rowNumber.", :forest_number".$rowNumber.",
                    :nursery_number".$rowNumber.", :other_outside_structure_number".$rowNumber.", :protected_monument_number".$rowNumber.", :rec_center_number".$rowNumber.",
                    :running_track_number".$rowNumber.", :shops_number".$rowNumber.", :tennis_court_number".$rowNumber.",
                    :density_typology".$rowNumber.", :insee_code".$rowNumber.", :population_strata".$rowNumber."
                ),";

                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                $organizationsSlug = $this->stringToArrayOrNull((string) $cells[3]->getValue());

                $sqlParams["organization_type_id".$rowNumber] = isset($organizationTypesBySlug[$this->redoSlug($organizationsSlug[0])]) ? $organizationTypesBySlug[$this->redoSlug($organizationsSlug[0])]->getId() : null;
                $sqlParams["perimeter_id".$rowNumber] = isset($perimetersById[(int) $cells[32]->getValue()]) ? (int) $cells[32]->getValue() : null;
                $sqlParams["perimeter_department_id".$rowNumber] = isset($perimetersById[(int) $cells[33]->getValue()]) ? (int) $cells[33]->getValue() : null;
                $sqlParams["perimeter_region_id".$rowNumber] = isset($perimetersById[(int) $cells[34]->getValue()]) ? (int) $cells[34]->getValue() : null;
                $sqlParams["backer_id".$rowNumber] = isset($backersById[(int) $cells[50]->getValue()]) ? (int) $cells[50]->getValue() : null;
                
                $sqlParams["name".$rowNumber] = (string) $cells[1]->getValue();
                $sqlParams["slug".$rowNumber] = (string) $cells[2]->getValue();
                $sqlParams["address".$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams["city_name".$rowNumber] = $this->stringOrNull((string) $cells[5]->getValue());
                $sqlParams["zip_code".$rowNumber] = $this->stringOrNull((string) $cells[6]->getValue());
                
                $sqlParams["siren_code".$rowNumber] = $this->stringOrNull((string) $cells[7]->getValue());
                $sqlParams["siret_code".$rowNumber] = $this->stringOrNull((string) $cells[8]->getValue());
                $sqlParams["ape_code".$rowNumber] = $this->stringOrNull((string) $cells[9]->getValue());
                
                $sqlParams["inhabitants_number".$rowNumber] = $this->intOrNull((string) $cells[10]->getValue());
                $sqlParams["voters_number".$rowNumber] = $this->intOrNull((string) $cells[11]->getValue());
                $sqlParams["corporates_number".$rowNumber] = $this->intOrNull((string) $cells[12]->getValue());
                $sqlParams["associations_number".$rowNumber] = $this->intOrNull((string) $cells[13]->getValue());
                
                $sqlParams["municipal_roads".$rowNumber] = $this->intOrNull((string) $cells[14]->getValue());
                $sqlParams["departmental_roads".$rowNumber] = $this->intOrNull((string) $cells[15]->getValue());
                $sqlParams["tram_roads".$rowNumber] = $this->intOrNull((string) $cells[16]->getValue());
                
                $sqlParams["lamppost_number".$rowNumber] = $this->intOrNull((string) $cells[17]->getValue());
                $sqlParams["library_number".$rowNumber] = $this->intOrNull((string) $cells[18]->getValue());
                $sqlParams["medialibrary_number".$rowNumber] = $this->intOrNull((string) $cells[19]->getValue());
                $sqlParams["theater_number".$rowNumber] = $this->intOrNull((string) $cells[20]->getValue());
                $sqlParams["museum_number".$rowNumber] = $this->intOrNull((string) $cells[21]->getValue());
                $sqlParams["kindergarten_number".$rowNumber] = $this->intOrNull((string) $cells[22]->getValue());
                
                $sqlParams["primary_school_number".$rowNumber] = $this->intOrNull((string) $cells[23]->getValue());
                $sqlParams["middle_school_number".$rowNumber] = $this->intOrNull((string) $cells[24]->getValue());
                $sqlParams["high_school_number".$rowNumber] = $this->intOrNull((string) $cells[25]->getValue());
                $sqlParams["university_number".$rowNumber] = $this->intOrNull((string) $cells[26]->getValue());
                
                $sqlParams["swimming_pool_number".$rowNumber] = $this->intOrNull((string) $cells[27]->getValue());
                $sqlParams["place_of_worship_number".$rowNumber] = $this->intOrNull((string) $cells[28]->getValue());
                $sqlParams["cemetery_number".$rowNumber] = $this->intOrNull((string) $cells[29]->getValue());
                
                try {
                    $timeCrate = new \DateTime(date((string) $cells[30]->getValue()));
                } catch (\Exception $exception) {
                    $timeCrate = new \DateTime(date('Y-m-d H:i:s'));
                }
                try {
                    $dateCreate = new \DateTime(date((string) $cells[30]->getValue()));
                } catch (\Exception $exception) {
                    $dateCreate = new \DateTime(date('Y-m-d'));
                }
                try {
                    $timeUpdate = new \DateTime(date((string) $cells[31]->getValue()));
                } catch (\Exception $exception) {
                    $timeUpdate = null;
                }
                try {
                    $timeImported = new \DateTime(date((string) $cells[35]->getValue()));
                } catch (\Exception $exception) {
                    $timeImported = null;
                }
                $sqlParams["time_create".$rowNumber] = $timeCrate->format('Y-m-d H:i:s');
                $sqlParams["date_create".$rowNumber] = $dateCreate->format('Y-m-d');
                $sqlParams["time_update".$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;
                $sqlParams["imported_time".$rowNumber] = $timeImported ? $timeImported->format('Y-m-d H:i:s') : null;
                $sqlParams["is_imported".$rowNumber] = $this->stringToBool((string) $cells[36]->getValue());
                $sqlParams["intercommunality_type".$rowNumber] = $this->stringOrNull((string) $cells[37]->getValue());

                $sqlParams["bridge_number".$rowNumber] = $this->intOrNull((string) $cells[38]->getValue());
                $sqlParams["cinema_number".$rowNumber] = $this->intOrNull((string) $cells[39]->getValue());
                $sqlParams["covered_sporting_complex_number".$rowNumber] = $this->intOrNull((string) $cells[40]->getValue());
                $sqlParams["football_field_number".$rowNumber] = $this->intOrNull((string) $cells[41]->getValue());
                $sqlParams["forest_number".$rowNumber] = $this->intOrNull((string) $cells[42]->getValue());
                
                $sqlParams["nursery_number".$rowNumber] = $this->intOrNull((string) $cells[43]->getValue());
                $sqlParams["other_outside_structure_number".$rowNumber] = $this->intOrNull((string) $cells[44]->getValue());
                $sqlParams["protected_monument_number".$rowNumber] = $this->intOrNull((string) $cells[45]->getValue());
                $sqlParams["rec_center_number".$rowNumber] = $this->intOrNull((string) $cells[46]->getValue());
                
                $sqlParams["running_track_number".$rowNumber] = $this->intOrNull((string) $cells[47]->getValue());
                $sqlParams["shops_number".$rowNumber] = $this->intOrNull((string) $cells[48]->getValue());
                $sqlParams["tennis_court_number".$rowNumber] = $this->intOrNull((string) $cells[49]->getValue());
                
                $sqlParams["density_typology".$rowNumber] = $this->stringOrNull((string) $cells[51]->getValue());
                $sqlParams["insee_code".$rowNumber] = $this->stringOrNull((string) $cells[52]->getValue());
                $sqlParams["population_strata".$rowNumber] = $this->stringOrNull((string) $cells[53]->getValue());
                // $sqlParams["old_id".$rowNumber] = (int) $cells[0]->getValue();


                // $entity = new Organization();
                // $entity->setOldId((int) $cells[0]->getValue());
                // $entity->setName((string) $cells[1]->getValue());
                // $entity->setSlug((string) $cells[2]->getValue());
                // $entity->setOrganizationType(isset($organizationTypesBySlug[(string) $cells[3]->getValue()]) ? $organizationTypesBySlug[(string) $cells[3]->getValue()] : null);
                // $entity->setAddress($this->stringOrNull((string) $cells[4]->getValue()));
                // $entity->setCityName($this->stringOrNull((string) $cells[5]->getValue()));
                // $entity->setZipCode($this->stringOrNull((string) $cells[6]->getValue()));
                // $entity->setSirenCode($this->stringOrNull((string) $cells[7]->getValue()));
                // $entity->setSiretCode($this->stringOrNull((string) $cells[8]->getValue()));
                // $entity->setApeCode($this->stringOrNull((string) $cells[9]->getValue()));
                // $entity->setInhabitantsNumber($this->intOrNull((string) $cells[10]->getValue()));
                // $entity->setVotersNumber($this->intOrNull((string) $cells[11]->getValue()));
                // $entity->setCorporatesNumber($this->intOrNull((string) $cells[12]->getValue()));
                // $entity->setAssociationsNumber($this->intOrNull((string) $cells[13]->getValue()));
                // $entity->setMunicipalRoads($this->intOrNull((string) $cells[14]->getValue()));
                // $entity->setDepartmentalRoads($this->intOrNull((string) $cells[15]->getValue()));
                // $entity->setTramRoads($this->intOrNull((string) $cells[16]->getValue()));
                // $entity->setLamppostNumber($this->intOrNull((string) $cells[17]->getValue()));
                // $entity->setLibraryNumber($this->intOrNull((string) $cells[18]->getValue()));
                // $entity->setMedialibraryNumber($this->intOrNull((string) $cells[19]->getValue()));
                // $entity->setTheaterNumber($this->intOrNull((string) $cells[20]->getValue()));
                // $entity->setMuseumNumber($this->intOrNull((string) $cells[21]->getValue()));
                // $entity->setKindergartenNumber($this->intOrNull((string) $cells[22]->getValue()));
                // $entity->setPrimarySchoolNumber($this->intOrNull((string) $cells[23]->getValue()));
                // $entity->setMiddleSchoolNumber($this->intOrNull((string) $cells[24]->getValue()));
                // $entity->setHighSchoolNumber($this->intOrNull((string) $cells[25]->getValue()));
                // $entity->setUniversityNumber($this->intOrNull((string) $cells[26]->getValue()));
                // $entity->setSwimmingPoolNumber($this->intOrNull((string) $cells[27]->getValue()));
                // $entity->setPlaceOfWorshipNumber($this->intOrNull((string) $cells[28]->getValue()));
                // $entity->setCemeteryNumber($this->intOrNull((string) $cells[29]->getValue()));
                // try {
                //     $entity->setTimeCreate(new \DateTime(date((string) $cells[30]->getValue())));
                // } catch (\Exception $exception) {
                //     $entity->setTimeCreate(new \DateTime(date('Y-m-d H:i:s')));
                // }
                // try {
                //     $entity->setDateCreate(new \DateTime(date((string) $cells[30]->getValue())));
                // } catch (\Exception $exception) {
                //     $entity->setDateCreate(new \DateTime(date('Y-m-d')));
                // }
                // try {
                //     $entity->setTimeUpdate(new \DateTime(date((string) $cells[31]->getValue())));
                // } catch (\Exception $exception) {
                // }
                // $entity->setPerimeter(isset($perimetersById[(int) $cells[32]->getValue()]) ? $perimetersById[(int) $cells[32]->getValue()] : null);
                // $entity->setPerimeterDepartment(isset($perimetersById[(int) $cells[33]->getValue()]) ? $perimetersById[(int) $cells[33]->getValue()] : null);
                // $entity->setPerimeterRegion(isset($perimetersById[(int) $cells[34]->getValue()]) ? $perimetersById[(int) $cells[34]->getValue()] : null);
                // try {
                //     $entity->setImportedTime(new \DateTime(date((string) $cells[35]->getValue())));
                // } catch (\Exception $exception) {
                // }
                // $entity->setIsImported($this->stringToBool((string) $cells[36]->getValue()));
                // $entity->setIntercommunalityType($this->stringOrNull((string) $cells[37]->getValue()));
                // $entity->setBridgeNumber($this->intOrNull((string) $cells[38]->getValue()));
                // $entity->setCinemaNumber($this->intOrNull((string) $cells[39]->getValue()));
                // $entity->setCoveredSportingComplexNumber($this->intOrNull((string) $cells[40]->getValue()));
                // $entity->setFootballFieldNumber($this->intOrNull((string) $cells[41]->getValue()));
                // $entity->setForestNumber($this->intOrNull((string) $cells[42]->getValue()));
                // $entity->setNurseryNumber($this->intOrNull((string) $cells[43]->getValue()));
                // $entity->setOtherOutsideStructureNumber($this->intOrNull((string) $cells[44]->getValue()));
                // $entity->setProtectedMonumentNumber($this->intOrNull((string) $cells[45]->getValue()));
                // $entity->setRecCenterNumber($this->intOrNull((string) $cells[46]->getValue()));
                // $entity->setRunningTrackNumber($this->intOrNull((string) $cells[47]->getValue()));
                // $entity->setShopsNumber($this->intOrNull((string) $cells[48]->getValue()));
                // $entity->setTennisCourtNumber($this->intOrNull((string) $cells[49]->getValue()));
                // $entity->setTennisCourtNumber($this->intOrNull((string) $cells[49]->getValue()));
                // $entity->setBacker(isset($backersById[(int) $cells[50]->getValue()]) ? $backersById[(int) $cells[50]->getValue()] : null);
                // $entity->setDensityTypology($this->stringOrNull((string) $cells[51]->getValue()));
                // $entity->setInseeCode($this->stringOrNull((string) $cells[52]->getValue()));
                // $entity->setPopulationStrata($this->stringOrNull((string) $cells[53]->getValue()));
                
                // sauvegarde
                // $this->managerRegistry->getManager()->persist($entity);
                

                // if ($rowNumber % $nbByBatch == 0) {
                //     $this->managerRegistry->getManager()->flush();
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
        unset($backersById);
        unset($organizationTypesBySlug);


        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}