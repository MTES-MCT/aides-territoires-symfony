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

#[AsCommand(name: 'at:import_flux:region_sud', description: 'Import de flux région sud')]
class ImportFluxRegionSudCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux région sud';
    protected string $commandTextEnd = '>Import de flux région sud';

    protected ?string $importUniqueidPrefix = 'RSud_';
    protected ?int $idDataSource = 13;

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['Nom de l’aide'])) {
            return null;
        }
        $importUniqueid = $this->importUniqueidPrefix . md5($aidToImport['Nom de l’aide']);
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
        $importRaws = $this->getImportRaws($aidToImport, ['Date d’ouverture', 'Date de clôture']);
        $importRawObjectCalendar = $importRaws['importRawObjectCalendar'];
        $importRawObject = $importRaws['importRawObject'];

        $dateStart = null;
        try {
            $dateStart = new \DateTime($aidToImport['Date d’ouverture'] ?? null);
        } catch (\Exception $e) {
            $dateStart = null;
        }

        $dateSubmissionDeadline = null;
        try {
            $dateSubmissionDeadline = new \DateTime($aidToImport['Date de clôture'] ?? null);
        } catch (\Exception $e) {
            $dateSubmissionDeadline = null;
        }

        return [
            'importDataMention' => 'Ces données sont mises à disposition par le Conseil départemental de la Manche.',
            'importRawObjectCalendar' => $importRawObjectCalendar,
            'importRawObject' => $importRawObject,
            'name' => isset($aidToImport['Nom de l’aide']) ? strip_tags($aidToImport['Nom de l’aide']) : null,
            'nameInitial' => isset($aidToImport['Nom de l’aide']) ? strip_tags($aidToImport['Nom de l’aide']) : null,
            'description' => $this->concatHtmlFields($aidToImport, ['Chapo', 'Pour qui', 'Pourquoi candidater', 'Quelle est la nature de l’aide (type d’aide)', 'Plus d’infos']),
            'eligibility' => $this->concatHtmlFields($aidToImport, ['Quelles sont les critères d’éligibilité', 'Comment en bénéficier ']),
            'originUrl' => isset($aidToImport['Lien vers le descriptif complet']) ? $aidToImport['Lien vers le descriptif complet'] : null,
            'applicationUrl' => isset($aidToImport['Lien Je fais ma demande']) ? $aidToImport['Lien Je fais ma demande'] : null,
            'contact' => isset($aidToImport['Contact']) ? $this->getHtmlOrNull($aidToImport['Contact']) : null,
            'dateStart' => $dateStart,
            'dateSubmissionDeadline' => $dateSubmissionDeadline,
            'isCallForProject' => isset($aidToImport['AAP']) && $aidToImport['AAP'] == '1' ? true : false,
        ];
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['Les thématiques']) || !is_array($aidToImport['Les thématiques'])) {
            return $aid;
        }

        $mapping = [
            'Institution' => [
                'citoyennete',
            ],
            'Économie- Entreprise' => [
                'culture',
                'economie-circulaire',
                'circuits-courts-filieres',
                'economie-sociale-et-solidaire'
            ],
            'Culture' => [
                'culture'
            ],
            'Aménagement du territoire' => [
                'batiments-construction',
                'equipement-public',
                'espace-public'
            ],
            'Europe et international' => [
                'cooperation-transfrontaliere',
            ],
            'Santé' => [
                'sante'
            ],
            'Sport' => [
                'sport'
            ],
            'Emploi' => [
                'emploi'
            ],
            'Transports' => [
                'mobilite-pour-tous',                
            ],
            'Formation' => [
                'formation'
            ],
            'Tourisme' => [
                'tourisme'
            ],
            'Enseignement' => [
                'education'
            ],
            'Agriculture' => [
                'agriculture'
            ]
        ];

        $categories = explode(',', $aidToImport['Les thématiques']);
        foreach ($categories as $categoryName) {
            if (isset($mapping[$categoryName])) {
                foreach ($mapping[$categoryName] as $slugCategory) {
                    $category = $this->managerRegistry->getRepository(Category::class)->findOneBy([
                        'slug' => $slugCategory
                    ]);
                    if ($category instanceof Category) {
                        $aid->addCategory($category);
                    }
                }
            }
        }

        return $aid;
    }

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['Pour qui']) || !is_array($aidToImport['Pour qui'])) {
            return $aid;
        }

        $mapping = [
            'Collectivité' => [
                'commune',
                'epci'
            ],
            'Entreprise' => [
                'private-sector',
            ],
            'Jeune' => [
                'private-person'
            ],
            'Association' => [
                'association'
            ],
            'Établissement d’enseignement' => [
                'public-cies'
            ],
            'Particulier' => [
                'private-person'
            ]
        ];

        foreach ($mapping as $key => $values) {
            if (preg_match('/.*'.$key.'.*/i', $aidToImport['Pour qui'])) {
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

        return $aid;
    }

    protected function setAidTypes(array $aidToImport, Aid $aid): Aid
    {
        $aidTypes = $this->managerRegistry->getRepository(AidType::class)->findCustom([
            'slugs' => [
                AidType::SLUG_OTHER,
            ]
        ]);
        foreach ($aidTypes as $aidType) {
            $aid->addAidType($aidType);
        }
        return $aid;
    }

    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid
    {
        if (isset($aidToImport['Date d’ouverture']) && trim($aidToImport['Date d’ouverture']) !== ''
        && isset($aidToImport['Date de clôture']) && trim($aidToImport['Date de clôture']) !== ''
        ) {
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

}