<?php

namespace App\Command\ImportFlux;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidType;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'at:import_flux:pays_loire', description: 'Import de flux région Pays de la Loire')]
class ImportFluxPaysLoireCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux région Pays de la Loire';
    protected string $commandTextEnd = '>Import de flux région Pays de la Loire';

    protected ?string $importUniqueidPrefix = 'PDLL_';
    protected ?int $idDataSource = 2;

    protected function setAidTypesById(): void
    {
        $aidTypes = $this->managerRegistry->getRepository(AidType::class)->findAll();
        foreach ($aidTypes as $aidType) {
            $this->aidTypesById[$aidType->getId()] = $aidType;
        }
    }

    /**
     * appel le flux.
     *
     * @return array<int, mixed>
     */
    protected function callApi(): array
    {
        $aidsFromImport = [];
        $client = $this->getClient();

        for ($i = 0; $i < $this->nbPages; ++$i) {
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

                foreach ($content['records'] as $value) {
                    if (isset($value['fields'])) {
                        $aidsFromImport[] = $value['fields'];
                    }
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
            if (!count($aidsFromImport)) {
                throw new \Exception('Le flux ne contient aucune aide');
            }
        }

        return $aidsFromImport;
    }

    /**
     * retourne un identifiant unique pour l'import.
     *
     * @param array<mixed, mixed> $aidToImport
     */
    protected function getImportUniqueid(array $aidToImport): ?string
    {
        if (!isset($aidToImport['intervention_id'])) {
            return null;
        }

        // Utilisation de md5 pour des raisons historiques. Les données ne sont pas sensibles.
        return $this->importUniqueidPrefix . $aidToImport['intervention_id'];
    }

    /**
     * @param array<mixed, mixed> $aidToImport
     * @param array<mixed, mixed> $params
     *
     * @return array<mixed, mixed>
     */
    protected function getFieldsMapping(array $aidToImport, ?array $params = null): array
    {
        $dateStart = !empty($aidToImport['date_de_debut'])
            ? $this->getDateTimeOrNull($aidToImport['date_de_debut'])
            : null;
        $dateSubmissionDeadline = !empty($aidToImport['date_de_fin'])
            ? $this->getDateTimeOrNull($aidToImport['date_de_fin'])
            : null;

        $eligibility = !empty($aidToImport['aidconditions'])
            ? $this->getHtmlOrNull($aidToImport['aidconditions'])
            : null;
        $contactFullName = '';
        if (!empty($aidToImport['contact_prenom'])) {
            $contactFullName .= $aidToImport['contact_prenom'];
        }
        if (!empty($aidToImport['contact_nom'])) {
            $contactFullName .= ' ' . $aidToImport['contact_nom'];
        }
        $aidToImport['contact_fullname'] = $contactFullName;
        $contact1 = $this->concatHtmlFields(
            $aidToImport,
            ['direction', 'service', 'pole'],
            '<br />'
        );
        $contact2 = $this->concatHtmlFields(
            $aidToImport,
            ['contact_fullname', 'contact_email', 'tel'],
            '<br />'
        );
        $contactDetails = !empty($aidToImport['informations_contact'])
            ? $this->getHtmlOrNull($aidToImport['informations_contact'])
            : null;
        $contact =
            (string) $contact1 . '<br /><br />' .
            (string) $contact2;
        if (!empty($contactDetails)) {
            $contact .= '<br /><br />' . (string) $contactDetails;
        }
        $description = $this->concatHtmlFields(
            $aidToImport,
            ['aid_objet', 'aid_operations_ei'],
            '<br /><br />'
        );

        $return = [
            'importDataMention' => 'Ces données sont mises à disposition par '
                . 'le Conseil régional des Pays de la Loire.',
            'name' => !empty($aidToImport['aide_nom'])
                ? $this->cleanName($aidToImport['aide_nom']) : null,
            'nameInitial' => !empty($aidToImport['aide_nom'])
                ? $this->cleanName($aidToImport['aide_nom']) : null,
            'eligibility' => $eligibility,
            'contact' => $contact,
            'description' => $description,
            'originUrl' => !empty($aidToImport['source_info'])
                ? $this->getValidExternalUrlOrNull($aidToImport['source_info']) : null,
            'applicationUrl' => !empty($aidToImport['source_lien'])
                ? $this->getValidExternalUrlOrNull($aidToImport['source_lien']) : null,
            'dateStart' => $dateStart,
            'dateSubmissionDeadline' => $dateSubmissionDeadline,
        ];

        // on ajoute les données brut d'import pour comparer avec les données actuelles
        return $this->mergeImportDatas($return);
    }

    /**
     * @param array<mixed, mixed> $aidToImport
     */
    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        $categories = !empty($aidToImport['ss_thematique_libelle'])
            ? explode(';', $aidToImport['ss_thematique_libelle'])
            : [];

        foreach ($categories as $categoryName) {
            if (isset($this->aidCategoriesMapping[$categoryName])) {
                foreach ($this->aidCategoriesMapping[$categoryName] as $category) {
                    $aid->addCategory($category);
                }
            }
        }

        return $aid;
    }

    protected function setAidCategoriesMapping(): void
    {
        $mapping = [];
        $filename = '/src/Command/ImportFlux/datas/pays_de_la_loire_categories_mapping.csv';
        if (($handle = fopen($this->fileService->getProjectDir() . $filename, 'r')) !== false) {
            // Lire l'en-tête
            $header = fgetcsv($handle, 1000, ',');

            // Trouver les index des colonnes nécessaires
            $indexPaysLoire = array_search('Sous-thématique Pays de la Loire', $header);
            $indexAT1 = array_search('Sous-thématique AT 1', $header);
            $indexAT2 = array_search('Sous-thématique AT 2', $header);

            // Lire les lignes suivantes
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $mapping[$data[$indexPaysLoire]] = [];
                if (!empty($data[$indexAT1])) {
                    $mapping[$data[$indexPaysLoire]][] = $data[$indexAT1];
                }
                if (!empty($data[$indexAT2])) {
                    $mapping[$data[$indexPaysLoire]][] = $data[$indexAT2];
                }
            }
            fclose($handle);
        }

        // recupere toutes nos catégories et fait un tableau par nom
        $categories = $this->managerRegistry->getRepository(Category::class)->findAll();
        $categoriesByName = [];
        foreach ($categories as $category) {
            $categoriesByName[$category->getName()] = $category;
        }

        // on reparcours le tableau pour y associer l'entité Category au lieu d'une string.
        // Si non trouvé on supprime la valeur du tableau
        foreach ($mapping as $key => $value) {
            $newValues = [];
            foreach ($value as $subCategoryName) {
                $category = $categoriesByName[$subCategoryName] ?? null;
                if ($category instanceof Category) {
                    $newValues[] = $category;
                }
            }
            $mapping[$key] = $newValues;
        }

        $this->aidCategoriesMapping = $mapping;
    }

    /**
     * @param array<mixed, mixed> $aidToImport
     */
    protected function setAidTypes(array $aidToImport, Aid $aid): Aid
    {
        $mapping = [
            'Aide' => 1, // Subvention
            'Accompagnement' => 6, // Ingénierie technique
            'Aide en nature' => 5, // Autre aide financière
            'Appel à manifestations d\'intérêt' => 1, // Subvention
            'Appel à projets' => 5, // Autre aide financière
            'Avance remboursable' => 3, // Avance récupérable
            'Garantie' => 5, // Autre aide financière
            'Prêt' => 2, // Prêt
            'Prêt d\'honneur' => 2, // Prêt
            'Service' => 5, // Autre aide financière
            'Subvention' => 1, // Subvention
        ];

        if (isset($aidToImport['type_de_subvention'])) {
            $types = explode(';', $aidToImport['type_de_subvention']);
            foreach ($types as $type) {
                if (isset($mapping[$type])) {
                    $aidType = $this->aidTypesById[$mapping[$type]] ?? null;
                    if ($aidType instanceof AidType) {
                        $aid->addAidType($aidType);
                    }
                }
            }
        }

        return $aid;
    }

    /**
     * @param array<mixed, mixed> $aidToImport
     */
    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid
    {
        $temporalite = $aidToImport['temporalite'] ?? null;

        if ($aid->getDateStart() instanceof \DateTime && $aid->getDateSubmissionDeadline() instanceof \DateTime) {
            $aid->setAidRecurrence($this->aidRecurrenceOneOff);
        } elseif ('Permanent' == $temporalite) {
            $aid->setAidRecurrence($this->aidRecurrenceOnGoing);
        } elseif ('Temporaire' == $temporalite) {
            $aid->setAidRecurrence($this->aidRecurrenceOneOff);
        }

        return $aid;
    }

    protected function setInternalAidRecurrences(): void
    {
        $this->aidRecurrenceOneOff = $this->managerRegistry->getRepository(AidRecurrence::class)
            ->findOneBy(['slug' => AidRecurrence::SLUG_ONEOFF]);
        $this->aidRecurrenceOnGoing = $this->managerRegistry->getRepository(AidRecurrence::class)
            ->findOneBy(['slug' => AidRecurrence::SLUG_ONGOING]);
    }

    protected function setOrganizationTypesById(): void
    {
        $organizationTypes = $this->managerRegistry->getRepository(OrganizationType::class)->findAll();
        foreach ($organizationTypes as $organizationType) {
            $this->organizationTypesById[$organizationType->getId()] = $organizationType;
        }
    }

    /**
     * @param array<mixed, mixed> $aidToImport
     */
    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid
    {
        $mapping = [
            'Collectivités - Institutions - GIP' => [
                1, // Communes
                2, // Intercommunalités / Pays
                3, // Départements
                7, // Entreprises publiques locales (Sem, Spl, SemOp)
                6, // Établissements publics (écoles, bibliothèques…) / Services de l\'État
            ],
            'Association' => [
                8, // Association
            ],
            'Associations' => [
                8, // Association
            ],
            'Entreprise' => [
                9, // Entreprises privées
            ],
            'Entreprises' => [
                9, // Entreprises privées
            ],
            'Etablissements ESR - Organismes de recherche' => [
                12, // Recherche
            ],
            'Lycées et centres de formation' => [
                6, // Établissements publics (écoles, bibliothèques…) / Services de l\'État
            ],
            'Jeunes' => [
                10, // Particulier
            ],
            'Particuliers' => [
                10, // Particulier
            ],
        ];

        $audiences = !empty($aidToImport['aid_benef'])
            ? explode(';', $aidToImport['aid_benef'])
            : [];

        foreach ($audiences as $audienceName) {
            if (isset($mapping[$audienceName])) {
                foreach ($mapping[$audienceName] as $audienceId) {
                    if (isset($this->organizationTypesById[$audienceId])) {
                        $aid->addAidAudience($this->organizationTypesById[$audienceId]);
                    }
                }
            }
        }

        return $aid;
    }
}
