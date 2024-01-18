<?php

namespace App\Command\ImportFlux;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command\ImportFlux\ImportFluxCommand;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Category\Category;
use App\Entity\Keyword\Keyword;
use App\Entity\Organization\OrganizationType;

#[AsCommand(name: 'at:import_flux:ile_de_france', description: 'Import de flux ile de france')]
class ImportFluxIleDeFranceCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux ile de france';
    protected string $commandTextEnd = '>Import de flux ile de france';

    protected ?string $importUniqueidPrefix = 'IDF_';
    protected ?int $idDataSource = 4;

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['referenceAdministrative'])) {
            return null;
        }
        $importUniqueid = $this->importUniqueidPrefix . md5($aidToImport['referenceAdministrative']);
        return $importUniqueid;
    }

    protected function callApi()
    {
        $aidsFromImport = [];

        for ($i=0; $i<$this->nbPages; $i++) {
            $this->currentPage = $i;
            $importUrl = $this->dataSource->getImportApiUrl();
            if ($this->paginationEnabled) {
                $importUrl .= '?limit=' . $this->nbByPages . '&offset=' . ($this->currentPage * $this->nbByPages);
            }
            try {
                $response = $this->httpClientInterface->request(
                    'GET',
                    $importUrl,
                    $this->getApiOptions()
                );
                $content = $response->getContent();
                $content = $response->toArray();
    
                // retourne directement un tableau d'aides
                $aidsFromImport = $content;
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
            if (!count($aidsFromImport)) {
                throw new \Exception('Le flux ne contient aucune aide');
            }
        }


        return $aidsFromImport;
    }



    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        try {
            $importRaws = $this->getImportRaws($aidToImport, ['dateFinCampagne', 'dateOuvertureCampagne', 'dateDebutFuturCampagne', 'datePublicationSouhaitee']);
            $importRawObjectCalendar = $importRaws['importRawObjectCalendar'];
            $importRawObject = $importRaws['importRawObject'];
            $originUrl = 'https://www.iledefrance.fr/aides-appels-a-projets/'
                        . (isset($aidToImport['reference'])) ? strip_tags($aidToImport['reference']) : ''; 
            $applicationUrl = $originUrl;
    
    
    
    
            $dateStart = null;
            if (isset($line["dateOuvertureCampagne"])) {
                try {
                    $dateStart = \DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $line["dateOuvertureCampagne"]);
                } catch (\Exception $e) {
                    $dateStart = \DateTime::createFromFormat("Y-m-d\TH:i:s\Z", $line["dateOuvertureCampagne"]);
                }
            }
                
            $dateSubmissionDeadline = null;
            if (isset($line["dateFinCampagne"])) {
                try {
                    $dateSubmissionDeadline = \DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $line["dateFinCampagne"]);
                } catch (\Exception $e) {
                    $dateSubmissionDeadline = \DateTime::createFromFormat("Y-m-d\TH:i:s\Z", $line["dateFinCampagne"]);
                }
            }
    
            return [
                'importDataMention' => 'Ces données sont mises à disposition par le Conseil Régional d\'Île-de-France.',
                'importRawObjectCalendar' => $importRawObjectCalendar,
                'importRawObject' => $importRawObject,
                'name' => isset($aidToImport['title']) ? strip_tags($aidToImport['title']) : null,
                'nameInitial' => isset($aidToImport['title']) ? strip_tags($aidToImport['title']) : null,
                'description' => $this->concatHtmlFields($aidToImport, ['engagements', 'entete', 'notes'], '<br/>'),
                'originUrl' => $originUrl,
                'applicationUrl' => $applicationUrl,
                'contact' => isset($aidToImport['contact']) ? $this->getHtmlOrNull($aidToImport['contact']) : null,
                'eligibility' => $this->concatHtmlFields($aidToImport, ['publicsBeneficiairePrecision', 'modalite', 'objectif', 'demarches'], '<br/>'),
                
                
                
                'dateStart' => $dateStart,
                'dateSubmissionDeadline' => $dateSubmissionDeadline,
                'isCallForProject' => isset($aidToImport['AAP']) && $aidToImport['AAP'] == '1' ? true : false,
            ];
        } catch (\Exception $e) {
            return [];
        }
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

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['publicsBeneficiaire']) || !is_array($aidToImport['publicsBeneficiaire'])) {
            return $aid;
        }

        $mapping = [
            'Associations' => [
                OrganizationType::SLUG_ASSOCIATION,
            ],
            'Chercheurs' => [
                OrganizationType::SLUG_RESEARCHER,
            ],
            'Collectivités - Institutions' => [
                OrganizationType::SLUG_ECPI,
            ],
            'Entreprises' => [
                OrganizationType::SLUG_PRIVATE_SECTOR,
            ],
            'Jeunes' => [
                OrganizationType::SLUG_PRIVATE_PERSON,
            ],
            'Lycées et centres de formation' => [
                OrganizationType::SLUG_PUBLIC_CIES,
            ],
            'Particuliers' => [
                OrganizationType::SLUG_PRIVATE_PERSON,
            ],
            'Professionnels' => [
                OrganizationType::SLUG_PRIVATE_SECTOR,
            ],
        ];

        foreach ($aidToImport['publicsBeneficiaire'] as $publicsBeneficiaire) {
            if (!isset($publicsBeneficiaire['title'])) {
                continue;
            }
            foreach ($mapping as $key => $values) {
                if (preg_match('/.*'.str_replace(['/'], ['\/'], $key).'.*/i', $publicsBeneficiaire['title'])) {
                    foreach ($values as $value) {
                        $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy([
                            'slug' => $value
                        ]);
                        if ($organizationType instanceof OrganizationType) {
                            $aid->addAidAudience($organizationType);
                        }
                    }
                }
            }
        }

        return $aid;
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['competences']) || !is_array($aidToImport['competences'])) {
            return $aid;
        }
        $mapping = [
            'Action sociale' => [
                'cohesion-sociale',
            ],
            'Actions internationales' => [
                'cooperation-transfrontaliere',
            ],
            'Agriculture / Ruralité' => [
                'agriculture',
            ],
            'Aménagement du territoire' => [
                'espace-public'
            ],
            'Apprentissage' => [
                'education',
            ],
            'Citoyenneté' => [
                'citoyennete'
            ],
            'Culture' => [
                'culture',
            ],
            'Développement économique' => [
                'attractivite',
            ],
            'Enseignement supérieur' => [
                'education',
            ],
            'Environnement' => [
                'biodiversite',
            ],
            'Europe' => [
                'cooperation-transfrontaliere',
            ],
            'FSS' => [
                'sante'
            ],
            'Formation professionnelle' => [
                'formation',
            ],
            'Fret' => [
                'commerces-et-services',
            ],
            'Logement' => [
                'logement-habitat',
            ],
            'Loisirs' => [
                'sport',
            ],
            'Lycées' => [
                'education',
            ],
            'Recherche' => [
                'innovation-et-recherche',
            ],
            'Route' => [
                'transports-collectifs-et-optimisation-des-trafics',
            ],
            'Santé' => [
                'sante',
            ],
            'Sport' => [
                'sport',
            ],
            'Tourisme' => [
                'tourisme'
            ],
            'Transports en commun' => [
                'transports-collectifs-et-optimisation-des-trafics',
            ],
            'Vélo' => [
                'amenagement-de-lespace-public-et-modes-actifs',
            ],

        ];

        foreach ($aidToImport['competences'] as $competence) {
            if (!isset($competence['title'])) {
                continue;
            }
            foreach ($mapping as $key => $values) {
                if (preg_match('/.*'.str_replace(['/'], ['\/'], $key).'.*/i', $competence['title'])) {
                    foreach ($values as $value) {
                        $category = $this->managerRegistry->getRepository(Category::class)->findOneBy([
                            'slug' => $value
                        ]);
                        if ($category instanceof Category) {
                            $aid->addCategory($category);
                        }
                    }
                }
            }
        }

        return $aid;
    }

    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid
    {
        $aidRecurrence = null;
        if (!isset($aidToImport['dateDebutFuturCampagne']) || trim($aidToImport['dateDebutFuturCampagne']) == '') {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => AidRecurrence::SLUG_RECURRING]);
        } else if (!isset($aidToImport['dateFinCampagne']) || trim($aidToImport['dateFinCampagne']) == '') {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => AidRecurrence::SLUG_ONEOFF]);
        } else {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => AidRecurrence::SLUG_ONGOING]);
        }

        if ($aidRecurrence instanceof AidRecurrence) {
            $aid->setAidRecurrence($aidRecurrence);
        }
        return $aid;
    }

    protected function setAidTypes(array $aidToImport, Aid $aid): Aid
    {
        $aidType = $this->managerRegistry->getRepository(AidType::class)->findOneBy([
            'slug' => AidType::SLUG_GRANT
        ]);

        if ($aidType instanceof AidType) {
            $aid->addAidType($aidType);
        }

        return $aid;
    }

    protected function setKeywords(array $aidToImport, Aid $aid): Aid
    {
        $categories = $aidToImport['competences'] ?? [];

        foreach ($categories as $category) {
            $keyword = $this->managerRegistry->getRepository(Keyword::class)->findOneBy([
                'name' => $category['title']
            ]);
            if (!$keyword instanceof Keyword) {
                $keyword = new Keyword();
                $keyword->setName($category['title']);
            }
            $aid->addKeyword($keyword);
        }
        return $aid;
    }
}