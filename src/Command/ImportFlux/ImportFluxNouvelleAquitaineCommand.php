<?php

namespace App\Command\ImportFlux;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command\ImportFlux\ImportFluxCommand;
use App\Entity\Aid\Aid;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;

#[AsCommand(name: 'at:import_flux:nouvelle_aquitaine', description: 'Import de flux région nouvelle aquitaine')]
class ImportFluxNouvelleAquitaineCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux région nouvelle aquitaine';
    protected string $commandTextEnd = '>Import de flux région nouvelle aquitaine';

    protected ?string $importUniqueidPrefix = 'NOUVELLE_AQUITAINE_';
    protected ?int $idDataSource = 1;

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['guid'])) {
            return null;
        }

        // Utilisation de md5 pour des raisons historiques. Les données ne sont pas sensibles.
        return $this->importUniqueidPrefix . md5($aidToImport['guid']);
    }

    protected function callApi()
    {
        $aidsFromImport = [];
        $client = $this->getClient();

        for ($i = 0; $i < $this->nbPages; $i++) {
            $this->currentPage = $i;
            $importUrl = $this->dataSource->getImportApiUrl();

            try {
                $response = $client->request(
                    'GET',
                    $importUrl,
                    $this->getApiOptions()
                );

                $content = $response->getContent();

                // Convertit le contenu XML en un objet SimpleXMLElement
                $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);

                // Convertit l'objet SimpleXMLElement en un tableau
                $json = json_encode($xml);
                $content = json_decode($json, true);

                // Assigne les items du flux à $aidToImport
                if (isset($content['channel']['item'])) {
                    $aidsFromImport = $content['channel']['item'];
                } else {
                    throw new \Exception('Le flux ne contient aucun item');
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


    protected function getApiOptions(): array
    {
        return [
            'headers' => [
                'Accept' => 'application/rss+xml',
            ],
        ];
    }

    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        $dateStart = (isset($aidToImport['pubDate']) && $aidToImport['pubDate'] !== '' && $aidToImport['pubDate'] !== null) ? \DateTime::createFromFormat('D, d M y H:i:s O', $aidToImport['pubDate']) : null;
        if ($dateStart instanceof \DateTime) {
            // Force pour éviter les différence sur le fuseau horaire
            $dateStart = new \DateTime(date($dateStart->format('Y-m-d')));
            // Force les heures, minutes, et secondes à 00:00:00
            $dateStart->setTime(0, 0, 0);
        }
        $description = $this->concatHtmlFields($aidToImport, ['description']);
        if (isset($aidToImport['source']) && $aidToImport['source'] !== '') {
            $description .= '<h2>Source :</h2><div>' . $aidToImport['source'] . '</div>';
        }
        $return = [
            'importDataMention' => 'Ces données sont mises à disposition par le Conseil régional de Nouvelle-Aquitaine .',
            'name' => isset($aidToImport['title']) ? $this->cleanName($aidToImport['title']) : null,
            'nameInitial' => isset($aidToImport['title']) ? $this->cleanName($aidToImport['title']) : null,
            'description' => $description,
            'originUrl' => isset($aidToImport['guid']) ? $aidToImport['guid'] : null,
            'dateStart' => $dateStart,
        ];

        // on ajoute les données brut d'import pour comparer avec les données actuelles
        return $this->mergeImportDatas($return);
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        // les categories du flux
        if (!isset($aidToImport['category'])) {
            return $aid;
        }
        $categoriesToImport = explode(',', $aidToImport['category']);

        // le mapping avec notre base
        $mapping = [
            'Économie territoriale' => 47,
            'Égalité' => 34,
            'Europe et international' => 77,
            'Foncier' => 21,
            'Infrastructures' => 69,
            'Logement' => 71,
            'Politique contractuelle' => 19,
            'Politique de la ville' => 19,
            'Santé' => 39,
            'Solidarité' => 37,
            'Sport' => 11,
            'Tiers-lieux' => 45,
            'Transports' => 85,
            'Vie associative' => 37,
            
            'Cinéma et audiovisuel' => 99,
            'Disque et livre' => 104,
            'Éducation artistique et culturelle' => 8,
            'Équipements culturels' => 68,
            'Langues et cultures régionales' => 8,
            'Manifestations culturelles' => 8,
            'Patrimoine et inventaire' => 7,
            
            'Agriculture' => 48,
            'Agroalimentaire' => 48,
            'Artisanat' => 83,
            'Bio' => 47,
            'Bois et forêt' => 14,
            'Chimie et matériaux' => 100,
            'Création d\'entreprise' => 53,
            'Cuir, luxe' => 83,
            'Développement d\'entreprise' => 53,
            'Développement international' => 77,
            'Économie culturelle' => 8,
            'Emploi' => 76,
            'ESS' => 50,
            'Export' => 77,
            'Filières' => 47,
            'Financement' => 78,
            'Formation professionnelle' => 43,
            'Innovation' => 53,
            'Local, bureau' => 47,
            'Numérique' => 44,
            'Pêche' => 6,
            'Performance et compétitivité' => 78,
            'Photonique' => 53,
            'Recherche' => 53,
            'Reprise d\'entreprise' => 51,
            'Start-up' => 53,
            'Tourisme' => 12,
            'Transmission et mutation d\'activité' => 51,
            
            'Accompagnement scolaire' => 40,
            'Apprentissage' => 40,
            'Collèges' => 40,
            'Éducation et formation' => 40,
            'Engagement et citoyenneté' => 38,
            'Enseignement supérieur' => 40,
            'Insertion professionnelle' => 43,
            'Lycées' => 40,
            'Mobilité internationale' => 40,
            'Orientation' => 40,
            'Sanitaire et social' => 40,
        
            'Biodiversité' => 67,
            'Climat' => 23,
            'Déchets' => 28,
            'Économie circulaire' => 46,
            'Économies d\'énergie' => 25,
            'Énergies renouvelables' => 23,
            'Environnement' => 23,
            'Littoral' => 102,
        ];

        // Les catégories en base par id
        $categories = $this->managerRegistry->getRepository(Category::class)->findBy([
            'id' => array_unique(array_values($mapping))
        ]);
        $categoriesById = [];
        foreach ($categories as $category) {
            $categoriesById[$category->getId()] = $category;
        }

        foreach ($categoriesToImport as $categoryToImport) {
            $categoryToImport = trim($categoryToImport);
            if (isset($mapping[$categoryToImport])) {
                $categoryId = $mapping[$categoryToImport];
                if (isset($categoriesById[$categoryId])) {
                    $aid->addCategory($categoriesById[$categoryId]);
                }
            }
        }

        return $aid;
    }

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['category'])) {
            return $aid;
        }
        $audiences = explode(',', $aidToImport['category']);

        $mapping = [
            'Agriculteur' => [11],
            'Apprenti' => [10],
            'Association' => [8],
            'Collectivité territoriale' => [1, 2, 3, 4],
            'Collégien' => [10],
            'Demandeur d\'emploi' => [10],
            'Entreprise' => [9],
            'Établissement public' => [6],
            'Étudiant' => [10],
            'Incubateur, Pépinière et Tiers-lieu' => [8],
            'Jeune actif' => [10],
            'Laboratoire de recherche' => [12],
            'Lycéen' => [10],
            'Organisation professionnelle' => [9],
            'Particulier' => [10],
            'Salarié' => [10],
            'Université, Enseignement supérieur, Recherche' => [6],
        ];
        // on recupere toutes les valeurs du tableau dans un tableau unique
        $flattenedMapping = array_unique(array_merge(...array_values($mapping)));

        $organizationTypes = $this->managerRegistry->getRepository(OrganizationType::class)->findBy([
            'id' => $flattenedMapping
        ]);
        $organizationTypesById = [];
        foreach ($organizationTypes as $organizationType) {
            $organizationTypesById[$organizationType->getId()] = $organizationType;
        }

        foreach ($audiences as $audienceName) {
            $audienceName = trim($audienceName);
            if (isset($mapping[$audienceName])) {
                foreach ($mapping[$audienceName] as $idAudience) {
                    if (isset($organizationTypesById[$idAudience])) {
                        $aid->addAidAudience($organizationTypesById[$idAudience]);
                    }
                }
            }
        }

        return $aid;
    }
}
