<?php

namespace App\Command\ImportFlux;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command\ImportFlux\ImportFluxCommand;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Category\Category;
use App\Entity\Keyword\Keyword;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;

#[AsCommand(name: 'at:import_flux:ademe_agir', description: 'Import de flux ADEME AGIR')]
class ImportFluxAdemeAgirCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux ADEME AGIR';
    protected string $commandTextEnd = '>Import de flux ADEME AGIR';

    protected ?string $importUniqueidPrefix = 'AGIR_';
    protected ?int $idDataSource = 10;

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['id'])) {
            return null;
        }
        $importUniqueid = $this->importUniqueidPrefix . $aidToImport['id'];
        return $importUniqueid;
    }

    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        $keys = ['date_debut', 'date_fin'];

        $importRawObjectCalendar = [];
        foreach ($keys as $key) {
            if (isset($aidToImport[$key])) {
                $importRawObjectCalendar[$key] = $aidToImport[$key];
            }
        }
        if (count($importRawObjectCalendar) == 0) {
            $importRawObjectCalendar = null;
        }

        $importRawObject = $aidToImport;
        foreach ($keys as $key) {
            if (isset($importRawObject[$key])) {
                unset($importRawObject[$key]);
            }
        }

        $isCallForProject = false;
        if (isset($aidToImport['type']) && $aidToImport['type'] == 'AAP') {
            $isCallForProject = true;
        }

        $dateStart = null;
        try {
            $dateStart = new \DateTime($aidToImport['date_debut'] ?? null);
        } catch (\Exception $e) {
            $dateStart = null;
        }

        $dateSubmissionDeadline = null;
        try {
            $dateSubmissionDeadline = new \DateTime($aidToImport['submission_deadline'] ?? null);
        } catch (\Exception $e) {
            $dateSubmissionDeadline = null;
        }

        $eligibility = null;
        if (isset($aidToImport['couverture_geo']) && (int) $aidToImport['couverture_geo'] == 4) {
            $regionCodes = $aidToImport['regions'] ?? [];
            foreach ($regionCodes as $regionCode) {
                $region = $this->managerRegistry->getRepository(Perimeter::class)->findOneBy([
                    'code' => $regionCode,
                    'scale' => Perimeter::SCALE_REGION
                ]);
                if ($region instanceof Perimeter) {
                    $eligibility .= $region->getName() . ' ';
                }
            }

            $eligibility = 'Ce dispositif est applicable uniquement aux régions suivantes : '.trim($eligibility);
        }

        return [
            'importDataMention' => 'Ces données sont mises à disposition par l\'ADEME.',
            'importRawObjectCalendar' => $importRawObjectCalendar,
            'importRawObject' => $importRawObject,
            'name' => isset($aidToImport['titre']) ? strip_tags($aidToImport['titre']) : null,
            'nameInitial' => isset($aidToImport['titre']) ? strip_tags($aidToImport['titre']) : null,
            'description' => isset($aidToImport['description_longue']) ? $this->htmlSanitizerInterface->sanitize($aidToImport['description_longue']) : null,
            'originUrl' => isset($aidToImport['url_agir']) ? $aidToImport['url_agir'] : null,
            'applicationUrl' => isset($aidToImport['url_agir']) ? $aidToImport['url_agir'] : null,
            'isCallForProject' => $isCallForProject,
            'dateStart' => $dateStart,
            'dateSubmissionDeadline' => $dateSubmissionDeadline,
            'eligibility' => $eligibility,
            'contact' => 'Pour contacter l\'Ademe ou candidater à l\'offre, veuillez cliquer sur le lien vers le descriptif complet.'
        ];
    }

    protected function getApiOptions(): array
    {
        $apiOptions = parent::getApiOptions();
        $apiOptions['headers']['client_id'] = $this->paramService->get('ademe_agir_api_username');
        $apiOptions['headers']['client_secret'] = $this->paramService->get('ademe_agir_api_password');
        return $apiOptions;
    }   

    protected function setAidTypes(array $aidToImport, Aid $aid): Aid
    {
        $aidTypes = $this->managerRegistry->getRepository(AidType::class)->findCustom([
            'slugs' => [
                AidType::SLUG_GRANT,
                AidType::SLUG_TECHNICAL_ENGINEERING
            ]
        ]);
        foreach ($aidTypes as $aidType) {
            $aid->addAidType($aidType);
        }
        return $aid;
    }

    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid
    {
        if (isset($aidToImport['date_debut']) && isset($aidToImport['date_fin'])) {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => AidRecurrence::SLUG_ONEOFF]);
        } else {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => AidRecurrence::SLUG_ONGOING]);
        }
        if ($aidRecurrence instanceof AidRecurrence) {
            $aid->setAidRecurrence($aidRecurrence);
        }
        return $aid;
    }

    protected function setAidSteps(array $aidToImport, Aid $aid): Aid
    {
        $aidSteps = $this->managerRegistry->getRepository(AidStep::class)->findCustom([
            'slugs' => [
                AidStep::SLUG_PREOP,
                AidStep::SLUG_OP,
                AidStep::SLUG_POSTOP,
            ]
        ]);
        foreach ($aidSteps as $aidStep) {
            $aid->addAidStep($aidStep);
        }
        return $aid;
    }

    protected function setPerimeter(array $aidToImport, Aid $aid): Aid
    {
        $couvertureGeo = isset($aidToImport['couverture_geo']) && isset($aidToImport['couverture_geo']['code'])
                    ? $aidToImport['couverture_geo']['code']
                    : null;

        if ((int) $couvertureGeo == 1) {
            $perimeter = $this->managerRegistry->getRepository(Perimeter::class)->findOneBy(['code' => Perimeter::CODE_FRANCE]);
        } else if ((int) $couvertureGeo == 2 || (int) $couvertureGeo == 3) {
            $perimeter = $this->managerRegistry->getRepository(Perimeter::class)->findOneBy(['code' => Perimeter::CODE_EUROPE]);
        } else if ((int) $couvertureGeo == 4) {
            $regionCodes = $aidToImport['regions'] ?? [];
            if (count($regionCodes) == 1) {
                $perimeter = $this->managerRegistry->getRepository(Perimeter::class)->findOneBy([
                    'code' => $regionCodes[0],
                    'scale' => Perimeter::SCALE_REGION
                ]);
            } else if (count($regionCodes) > 1) {
                $perimeterName = $this->perimeterService->getAdhocNameFromRegionCodes($regionCodes);
                $perimeter = $this->managerRegistry->getRepository(Perimeter::class)->findOneBy([
                    'name' => $perimeterName,
                    'scale' => Perimeter::SCALE_ADHOC
                ]);
                if (!$perimeter instanceof Perimeter) {
                    // on créer un nouveau périmètre adhoc
                    $perimeter = new Perimeter();
                    $perimeter->setName($perimeterName);
                    $perimeter->setScale(Perimeter::SCALE_ADHOC);
                    $perimeter->setContinent(Perimeter::SLUG_CONTINENT_DEFAULT);
                    $perimeter->setCountry(Perimeter::SLUG_COUNTRY_DEFAULT);
                    $perimeter->setCode(uniqid());
                    $perimeter->setIsVisibleToUsers(false);

                    // on met les régions dans le périmètre adhoc
                    foreach ($regionCodes as $regionCode) {
                        $region = $this->managerRegistry->getRepository(Perimeter::class)->findOneBy([
                            'code' => $regionCode,
                            'scale' => Perimeter::SCALE_REGION
                        ]);
                        if ($region instanceof Perimeter) {
                            $perimeter->addPerimetersFrom($region);
                        }
                    }

                    $this->managerRegistry->getManager()->persist($perimeter);
                }
            }
        } else {
            $perimeter = null;
        }

        if ($perimeter instanceof Perimeter) {
            $aid->setPerimeter($perimeter);
        }

        return $aid;
    }

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['cible_projet']) || !is_array($aidToImport['cible_projet'])) {
            return $aid;
        }

        // mapping audiences ADEME AGIR => [OrganizationType.slug] Aides-Territoires
        $mapping = [
            'SCA1' => [
                'association',
            ],
            'SCA2' => [
                'commune',
                'epci',
                'department',
                OrganizationType::SLUG_PUBLIC_CIES,
                OrganizationType::SLUG_PUBLIC_ORG,
            ],
            'SCA3' => [
                OrganizationType::SLUG_PRIVATE_SECTOR
            ],
            'SCA4' => [
                'researcher'
            ],
        ];

        foreach ($aidToImport['cible_projet'] as $cibleProjet) {
            if (isset($mapping[$cibleProjet])) {
                foreach ($mapping[$cibleProjet] as $slugOrganizationType) {
                    $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy([
                        'slug' => $slugOrganizationType
                    ]);
                    if ($organizationType instanceof OrganizationType) {
                        $aid->addAidAudience($organizationType);
                    }
                }
            }
        }

        return $aid;
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['thematiques']) || !is_array($aidToImport['thematiques'])) {
            return $aid;
        }

        // mapping ADEME AGIR => [Category.name, Category.slug] Aides-Territoires
        $mapping = $this->getCategoriesMapping();

        foreach ($aidToImport['thematiques'] as $thematique) {
            if (isset($mapping[$thematique])) {
                foreach ($mapping[$thematique] as $category) {
                    $category = $this->managerRegistry->getRepository(Category::class)->findOneBy([
                        'slug' => $category['slug']
                    ]);
                    if ($category instanceof Category) {
                        $aid->addCategory($category);
                    }
                }
            }
        }
        return $aid;
    }

    protected function setKeywords(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['thematiques']) || !is_array($aidToImport['thematiques'])) {
            return $aid;
        }

        // mapping ADEME AGIR => [Category.name, Category.slug] Aides-Territoires
        $mapping = $this->getCategoriesMapping();

        foreach ($aidToImport['thematiques'] as $thematique) {
            if (isset($mapping[$thematique])) {
                foreach ($mapping[$thematique] as $category) {
                    $keyword = $this->managerRegistry->getRepository(Keyword::class)->findOneBy([
                        'slug' => $category['slug']
                    ]);
                    if (!$keyword instanceof Keyword) {
                        // nouveau keyword, on le créer
                        $keyword = new Keyword();
                        $keyword->setName($category['name']);
                        $keyword->setSlug($category['slug']);
                        $this->managerRegistry->getManager()->persist($keyword);
                    }

                    // ajoute le keyword à l'aide
                    $aid->addKeyword($keyword);
                }
            }
        }
        return $aid;
    }

    private function getCategoriesMapping(): array
    {
        return [
            'ALIBIO' => [
                [
                    'name' => 'Alimentation',
                    'slug' => 'alimentation',
                ],
                [
                    'name' => 'Recyclage et valorisation des déchets',
                    'slug' => 'recyclage-valorisation',
                ],
            ],
            'DEMTER' => [
                [
                    'name' => 'Economie locale et circuits courts',
                    'slug' => 'circuits-courts-filieres',
                ],    
            ],
            'PRODUR' => [
                [
                    'name' => 'Consommation et production',
                    'slug' => 'consommation-et-production',
                ],
                [
                    'name' => 'Economie circulaire',
                    'slug' => 'economie-circulaire',
                ],
                [
                    'name' => 'Economie locale et circuits courts',
                    'slug' => 'circuits-courts-filieres'
                ],
            ],
            'REEREP' => [
                [
                    'name' => 'Emploi',
                    'slug' => 'emploi'
                ],
            ],
            'TRIREC' => [
                [
                    'name' => 'Recyclage et valorisation des déchets',
                    'slug' => 'recyclage-valorisation',
                ],
            ],
            'VALENR' => [
                [
                    'name' => 'Economie d\'énergie et rénovation énergétique',
                    'slug' => 'economie-denergie',
                ],
            ],
            'BOIBIO' => [
                [
                    'name' => 'Biodiversité',
                    'slug' => 'biodiversite',
                ],
                [
                    'name' => 'Forêts',
                    'slug' => 'forets'
                ],
            ],
            'EFFENR' => [
                [
                    'name' => 'Economie d\'énergie et rénovation énergétique',
                    'slug' => 'economie-denergie'
                ],
            ],
            'GEOTER' => [
                [
                    'name' => 'Réduction de l\'empreinte carbone',
                    'slug' => 'empreinte-carbone',
                ],
                [
                    'name' => 'Transition énergétique',
                    'slug' => 'transition-energetique',
                ],
                [
                    'name' => 'Réseaux de chaleur',
                    'slug' => 'reseaux-de-chaleur'
                ],               
            ],
            'ENRAUT' => [
                [
                    'name' => 'Transition énergétique',
                    'slug' => 'transition-energetique',
                ],
            ],
            'RECCHA' => [
                [
                    'name' => 'Réseaux de chaleur',
                    'slug' => 'reseaux-de-chaleur'
                ],   
            ],
            'RESCHF' => [
                [
                    'name' => 'Réseaux de chaleur',
                    'slug' => 'reseaux-de-chaleur'
                ],   
            ],
            'SOLAIR' => [
                [
                    'name' => 'Transition énergétique',
                    'slug' => 'transition-energetique',
                ],
            ],
            'MTR' => [
                [
                    'name' => 'Transports collectifs et optimisation des trafics routiers',
                    'slug' => 'transports-collectifs-et-optimisation-des-trafics',
                ],
                [
                    'name' => 'Mobilité partagée',
                    'slug' => 'mobilite-partagee',
                ],               
                [
                    'name' => 'Mobilité pour tous',
                    'slug' => 'mobilite-pour-tous',
                ],                    
            ],
            'QUA' => [
                [
                    'name' => 'Qualité de l\'air',
                    'slug' => 'qualite-de-lair',
                ],  
            ],
            'SAF' => [
                [
                    'name' => 'Sols',
                    'slug' => 'sols',
                ],  
                [
                    'name' => 'Agriculture et agroalimentaire',
                    'slug' => 'agriculture',
                ],                  
                [
                    'name' => 'Forêts',
                    'slug' => 'forets',
                ],                   
            ],
            'TOU' => [
                [
                    'name' => 'Tourisme',
                    'slug' => 'tourisme'
                ],  
            ],
            'HRBTOU' => [
                [
                    'name' => 'Tourisme',
                    'slug' => 'tourisme'
                ],  
                [
                    'name' => 'Logement et habitat',
                    'slug' => 'logement-habitat'
                ],  
            ],
            'RESTAU' => [
                'alimentation',
                [
                    'name' => 'Tourisme',
                    'slug' => 'tourisme'
                ],  
            ],
            'SLOTOU' => [
                [
                    'name' => 'Tourisme',
                    'slug' => 'tourisme'
                ],  
                [
                    'name' => 'Transition énergétique',
                    'slug' => 'transition-energetique',
                ],
            ],
            'UBA' => [
                [
                    'name' => 'Bâtiments et construction',
                    'slug' =>  'batiments-construction',
                ],  
            ],
            'CCL' => [
                [
                    'name' => 'Transition énergétique',
                    'slug' => 'transition-energetique',
                ],
                [
                    'name' => 'Réduction de l\'empreinte carbone',
                    'slug' => 'empreinte-carbone'
                ],
            ]
        ];
    }
}