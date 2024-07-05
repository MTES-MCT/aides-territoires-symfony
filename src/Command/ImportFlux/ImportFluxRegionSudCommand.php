<?php

namespace App\Command\ImportFlux;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command\ImportFlux\ImportFluxCommand;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;
use App\Repository\Aid\AidStepRepository;
use App\Repository\Aid\AidTypeRepository;
use Symfony\Component\HttpClient\CurlHttpClient;

#[AsCommand(name: 'at:import_flux:region_sud', description: 'Import de flux région sud')]
class ImportFluxRegionSudCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux région sud';
    protected string $commandTextEnd = '>Import de flux région sud';

    protected ?string $importUniqueidPrefix = 'RSud_';
    protected ?int $idDataSource = 13;

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['Uid'])) {
            return null;
        }
        return $this->importUniqueidPrefix . $aidToImport['Uid'];
    }

    protected function callApi()
    {
        $aidsFromImport = [];
        $client = $this->getClient();
        for ($i=0; $i<$this->nbPages; $i++) {
            $this->currentPage = $i;
            $importUrl = $this->dataSource->getImportApiUrl();
            if ($this->paginationEnabled) {
                $importUrl .= '?limit=' . $this->nbByPages . '&offset=' . ($this->currentPage * $this->nbByPages);
            }
            try {
                $response = $client->request(
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

    // visiblement le certificat à re changer. Je laisse en commentaire pour le moment
    // protected function getClient(): CurlHttpClient
    // {
    //     // place le certificat dans un fichier temporaire
    //     $cerificate = $this->paramService->get('certificat_region_sud');
    //     $certificatePath = $this->fileService->getUploadTmpDir() . '/certificat_region_sud.pem';
    //     file_put_contents($certificatePath, $cerificate);

    //     // combine les options avec le certificat
    //     $apiOptions = array_merge(
    //         [
    //             'cafile' => $certificatePath,
    //         ],
    //         $this->getApiOptions()
    //     );

    //     // creer le client
    //     return new CurlHttpClient($apiOptions);
    // }


    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        $dateStart = (isset($aidToImport['Date d’ouverture']) && $aidToImport['Date d’ouverture'] !== '' && $aidToImport['Date d’ouverture'] !== null) ? new \DateTime($aidToImport['Date d’ouverture']) : null;
        if ($dateStart instanceof \DateTime) {
            // Force pour éviter les différence sur le fuseau horaire
            $dateStart = new \DateTime(date($dateStart->format('Y-m-d')));
            // Force les heures, minutes, et secondes à 00:00:00
            $dateStart->setTime(0, 0, 0);
        }
        $dateSubmissionDeadline = (isset($aidToImport['Date de clôture']) && $aidToImport['Date de clôture'] !== '' && $aidToImport['Date de clôture'] !== null) ? new \DateTime($aidToImport['Date de clôture']) : null;
        if ($dateSubmissionDeadline instanceof \DateTime) {
            // Force pour éviter les différence sur le fuseau horaire
            $dateSubmissionDeadline = new \DateTime(date($dateSubmissionDeadline->format('Y-m-d')));
            // Force les heures, minutes, et secondes à 00:00:00
            $dateSubmissionDeadline->setTime(0, 0, 0);
        }

        $return = [
            'importDataMention' => 'Ces données sont mises à disposition par le Conseil Régional PACA.',
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

        // on ajoute les données brut d'import pour comparer avec les données actuelles
        return $this->mergeImportDatas($return);
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid // NOSONAR too complex
    {
        if (!isset($aidToImport['Les thématiques'])) {
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
            ],
            'Environnement' => [
                'biodiversite'
            ]
        ];

        $categories = explode(',', $aidToImport['Les thématiques']);

        foreach ($categories as $categoryName) {
            $categoryName = trim($categoryName);
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

        // Spécifique sur ce flux, les beneficiaires et les catégories sont mélangés
        $mapping = [
            'Collectivité' => [
                'commune',
                'epci',
                'department',
                'region',
                'special'
            ],
            'Entreprise' => [
                'private-sector'
            ],
            'Etablissement d\'enseignement' => [
                'public-org'
            ],
            'Association' => [
                'association'
            ],
            'Particulier' => [
                'private-person'
            ]
        ];

        $categories = explode(',', $aidToImport['Les thématiques']);

        foreach ($categories as $categoryName) {
            $categoryName = trim($categoryName);
            if (isset($mapping[$categoryName])) {
                foreach ($mapping[$categoryName] as $slugCategory) {
                    $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy([
                        'slug' => $slugCategory
                    ]);
                    if ($organizationType instanceof OrganizationType) {
                        $aid->addAidAudience($organizationType);
                    }
                }
            }
        }

        return $aid;
    }

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid // NOSONAR too complex
    {
        if (!isset($aidToImport['Pour qui'])) {
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
            'Etablissement d\'enseignement' => [
                'public-cies',
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

        // Spécifique sur ce flux, les beneficiaires et les catégories sont mélangés
        $mapping = [
            'Formation' => [
                'formation'
            ],
            'Transports' => [
                'transports-collectifs-et-optimisation-des-trafics'
            ]
        ];

        foreach ($mapping as $key => $values) {
            if (preg_match('/.*'.$key.'.*/i', $aidToImport['Pour qui'])) {
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

        return $aid;
    }

    protected function setAidTypes(array $aidToImport, Aid $aid): Aid
    {
        /** @var AidTypeRepository $aidTypeRepo */
        $aidTypeRepo = $this->managerRegistry->getRepository(AidType::class);

        $aidTypes = $aidTypeRepo->findCustom([
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
        /** @var AidStepRepository $aidStepRepo */
        $aidStepRepo = $this->managerRegistry->getRepository(AidStep::class);

        $aidSteps = $aidStepRepo->findCustom([
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

    protected function setIsCallForProject(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['Pour qui'])) {
            return $aid;
        }

        if (preg_match('/.*Appels à projets*/i', $aidToImport['Pour qui'])) {
            $aid->setIsCallForProject(true);
        }

        return $aid;
    }
}
