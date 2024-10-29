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
use App\Entity\Reference\KeywordReference;
use Symfony\Component\HttpClient\CurlHttpClient;

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
        return $this->importUniqueidPrefix . $aidToImport['referenceAdministrative'];
    }

    protected function callApi()
    {
        $aidsFromImport = [];
        $client = $this->getClient();

        for ($i = 0; $i < $this->nbPages; $i++) {
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

    protected function getClient(): CurlHttpClient
    {
        // place le certificat dans un fichier temporaire
        $cerificate = $this->paramService->get('certificat_ile_de_france');
        $certificatePath = $this->fileService->getUploadTmpDir() . '/certificat_ile_de_france.pem';
        file_put_contents($certificatePath, $cerificate);

        // combine les options avec le certificat
        $apiOptions = array_merge(
            [
                'cafile' => $certificatePath,
            ],
            $this->getApiOptions()
        );

        // creer le client
        return new CurlHttpClient($apiOptions);
    }


    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        try {
            $aidName = isset($aidToImport['libelle']) ? strip_tags((string) $aidToImport['libelle']) : null;
            if (!$aidName) {
                $aidName = isset($aidToImport['title']) ? strip_tags((string) $aidToImport['title']) : '';
            }

            $originUrl = 'https://www.iledefrance.fr/aides-et-appels-a-projets/' .
                $this->stringService->getSlug(str_replace(
                    ["'", "’", 'à'],
                    [''],
                    preg_replace('/(\d{2,4})[-.](\d{2})[-.](\d{2,4})/', '$1$2$3', $aidName)
                ));
            $applicationUrl = $originUrl;


            $dateStart = null;
            if (isset($aidToImport["dateOuvertureCampagne"])) {
                try {
                    $dateStart = \DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $aidToImport["dateOuvertureCampagne"]);
                } catch (\Exception $e) {
                    $dateStart = \DateTime::createFromFormat("Y-m-d\TH:i:s\Z", $aidToImport["dateOuvertureCampagne"]);
                }
            }
            if (!$dateStart instanceof \DateTime) {
                $dateStart = null;
            } else {
                // Force pour éviter les différence sur le fuseau horaire
                $dateStart = new \DateTime(date($dateStart->format('Y-m-d')));
                // Force les heures, minutes, et secondes à 00:00:00
                $dateStart->setTime(0, 0, 0);
            }

            $dateSubmissionDeadline = null;
            if (isset($aidToImport["dateFinCampagne"])) {
                try {
                    $dateSubmissionDeadline = \DateTime::createFromFormat(
                        "Y-m-d\TH:i:s.u\Z",
                        $aidToImport["dateFinCampagne"]
                    );
                } catch (\Exception $e) {
                    $dateSubmissionDeadline = \DateTime::createFromFormat(
                        "Y-m-d\TH:i:s\Z",
                        $aidToImport["dateFinCampagne"]
                    );
                }
            }
            if (!$dateSubmissionDeadline instanceof \DateTime) {
                $dateSubmissionDeadline = null;
            } else {
                // Force pour éviter les différence sur le fuseau horaire
                $dateSubmissionDeadline = new \DateTime(date($dateSubmissionDeadline->format('Y-m-d')));
                // Force les heures, minutes, et secondes à 00:00:00
                $dateSubmissionDeadline->setTime(0, 0, 0);
            }

            $return = [
                'importDataMention' => 'Ces données sont mises à disposition par le Conseil Régional d\'Île-de-France.',
                'name' => $aidName,
                'nameInitial' => $aidName,
                'description' => $this->concatHtmlFields($aidToImport, ['engagements', 'entete', 'notes'], '<br/>'),
                'originUrl' => $originUrl,
                'applicationUrl' => $applicationUrl,
                'contact' => isset($aidToImport['contact']) ? $this->getHtmlOrNull($aidToImport['contact']) : null,
                'eligibility' => $this->concatHtmlFields(
                    $aidToImport,
                    ['publicsBeneficiairePrecision', 'modalite', 'objectif', 'demarches'],
                    '<br/>'
                ),
                'dateStart' => $dateStart,
                'dateSubmissionDeadline' => $dateSubmissionDeadline,
                'isCallForProject' => isset($aidToImport['AAP']) && $aidToImport['AAP'] == '1' ? true : false,
            ];

            // on ajoute les données brut d'import pour comparer avec les données actuelles
            return $this->mergeImportDatas($return);
        } catch (\Exception $e) {
            return [];
        }
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

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid // NOSONAR too complex
    {
        if (!isset($aidToImport['publicsBeneficiaire']) || !is_array($aidToImport['publicsBeneficiaire'])) {
            return $aid;
        }

        $mapping = [
            'Associations' => [
                OrganizationType::SLUG_ASSOCIATION,
            ],
            'Association' => [
                OrganizationType::SLUG_ASSOCIATION,
            ],
            'Chercheurs' => [
                OrganizationType::SLUG_RESEARCHER,
            ],
            'Collectivités - Institutions' => [
                OrganizationType::SLUG_EPCI,
            ],
            'Commune' => [
                OrganizationType::SLUG_COMMUNE
            ],
            'EPCI' => [
                OrganizationType::SLUG_EPCI,
            ],
            'Département' => [
                OrganizationType::SLUG_DEPARTMENT,
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
            'Particulier' => [
                OrganizationType::SLUG_PRIVATE_PERSON,
            ],
            'Professionnels' => [
                OrganizationType::SLUG_PRIVATE_SECTOR,
            ],
            'Professionnel' => [
                OrganizationType::SLUG_PRIVATE_SECTOR,
            ],
            'Établissement de recherche et laboratoire' => [
                OrganizationType::SLUG_RESEARCHER
            ]
        ];


        // manquants
        // 0 => "Collectivité ou institution - Autre (GIP, copropriété, EPA...)"
        // 1 => "Collectivité ou institution - Bailleurs sociaux"
        // 2 => "Collectivité ou institution - EPT / Métropole du Grand Paris"
        // 3 => "Collectivité ou institution - Office de tourisme intercommunal"
        // 4 => "Établissement d'enseignement secondaire"
        // 5 => "Établissement d'enseignement supérieur"
        // 6 => "Établissement ou organismes de formation (OF, OPCO, FSS, CFA...)"

        foreach ($aidToImport['publicsBeneficiaire'] as $publicsBeneficiaire) {
            if (!isset($publicsBeneficiaire['title'])) {
                continue;
            }
            foreach ($mapping as $key => $values) {
                if (preg_match('/.*' . str_replace(['/'], ['\/'], $key) . '.*/i', $publicsBeneficiaire['title'])) {
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

    protected function setCategories(array $aidToImport, Aid $aid): Aid // NOSONAR too complex
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
                if (preg_match('/.*' . str_replace(['/'], ['\/'], $key) . '.*/i', $competence['title'])) {
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
        if (
            !isset($aidToImport['dateDebutFuturCampagne'])
            || trim($aidToImport['dateDebutFuturCampagne']) == ''
        ) {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)
            ->findOneBy(['slug' => AidRecurrence::SLUG_RECURRING]);
        } elseif (!isset($aidToImport['dateFinCampagne']) || trim($aidToImport['dateFinCampagne']) == '') {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)
            ->findOneBy(['slug' => AidRecurrence::SLUG_ONEOFF]);
        } else {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)
            ->findOneBy(['slug' => AidRecurrence::SLUG_ONGOING]);
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
            $keyword = $this->managerRegistry->getRepository(KeywordReference::class)->findOneBy([
                'name' => $category['title']
            ]);
            if ($keyword instanceof KeywordReference) {
                $aid->addKeywordReference($keyword->getParent() ?? $keyword);
            }
        }
        return $aid;
    }
}
