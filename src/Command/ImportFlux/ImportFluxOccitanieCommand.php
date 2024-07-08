<?php

namespace App\Command\ImportFlux;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command\ImportFlux\ImportFluxCommand;
use App\Entity\Aid\Aid;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use App\Entity\DataSource\DataSource;
use Symfony\Component\HttpClient\CurlHttpClient;

#[AsCommand(name: 'at:import_flux:occitanie', description: 'Import de flux Occitanie')]
class ImportFluxOccitanieCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux Occitanie';
    protected string $commandTextEnd = '>Import de flux Occitanie';

    protected ?string $importUniqueidPrefix = 'OCCITANIE_';
    protected ?int $idDataSource = 11;

    protected function getClient(): CurlHttpClient
    {
        // creer le client
        return new CurlHttpClient($this->getApiOptions());
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
                $content = json_decode($content, true);

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

    protected function findAid($aidToImport): ?Aid
    {
        try {
            // on recherche d'abprd par importUniqueid
            $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(
                [
                    'importUniqueid' => trim($this->getImportUniqueid($aidToImport))
                ]
            );
            if ($aid instanceof Aid) {
                return $aid;
            }

            // si non trouvé on regarde par nom initial et datasource
            $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(
                [
                    'nameInitial' => trim($aidToImport['nameInitial']),
                    'dataSource' => $this->managerRegistry->getRepository(DataSource::class)->find($this->idDataSource)
                ]
            );
            if ($aid instanceof Aid) {
                return $aid;
            }

            // si non trouvé on regarde par url et datasource
            $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(
                [
                    'originUrl' => trim($aidToImport['originUrl']),
                    'dataSource' => $this->managerRegistry->getRepository(DataSource::class)->find($this->idDataSource)
                ]
            );
            if ($aid instanceof Aid) {
                return $aid;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['id_article'])) {
            return null;
        }
        $importUniqueid = $this->importUniqueidPrefix . $aidToImport['id_article'];
        return substr($importUniqueid, 0, 200);
    }


    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        $timePublished = $this->getDateTimeOrNull($aidToImport['date_publication'] ?? null, ['keepTime' => true]);
        $datePublished = null;
        if ($timePublished instanceof \DateTime) {
            $datePublished = new \DateTime($timePublished->format('Y-m-d'));
        }
        $timeUpdate = $this->getDateTimeOrNull($aidToImport['date_modification'] ?? null);

        $return = [
            'name' => isset($aidToImport['titre']) ? strip_tags($aidToImport['titre']) : null,
            'nameInitial' => isset($aidToImport['titre']) ? strip_tags($aidToImport['titre']) : null,
            'timePublished' => $timePublished,
            'datePublished' => $datePublished,
            'description' => $this->concatHtmlFields($aidToImport, ['chapo', 'introduction']),
            'timeUpdate'=> $timeUpdate,
            'originUrl' => $aidToImport['url'] ?? null,
            'isCallForProject' => (isset($aidToImport['aides_appels_a_projets']) && $aidToImport['aides_appels_a_projets'] == 'Appels à projets') ? true : false,
            'contact' => "Pour contacter la Région Occitanie ou candidater à l'offre, veuillez cliquer sur le bouton 'Plus d'informations' ou sur le bouton 'Candidater à l'aide'.",
            'importDataMention' => 'Ces données sont mises à disposition par la Région Occitanie.',
        ];

        // on ajoute les données brut d'import pour comparer avec les données actuelles
        return $this->mergeImportDatas($return);
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        $categories = (isset($aidToImport['thematiques']) && $aidToImport['thematiques'])
                    ? explode(',', $aidToImport['thematiques'])
                    : [];
        $subCategories = (isset($aidToImport['sous_thematiques']) && $aidToImport['sous_thematiques'])
                    ? explode(',', $aidToImport['sous_thematiques'])
                    : [];
        $categories = array_merge($categories, $subCategories);

        // une partie du mapping à été fait sur les CategoryTheme et non sur les catégories
        $mappingThemes = [
            'Aménagement du territoire' => [
                'urbanisme-logement-amenagement'
            ],
            'Art de la scène' => [
                'culture-patrimoine-sports-tourisme',
            ],
            'Culture' => [
                'culture-patrimoine-sports-tourisme',
            ],
            'Eau' => [
                'eau'
            ],
            'Environnement - Climat' => [
                'nature-environnement-risques'
            ],
            'Solidarités - Santé - Égalités' => [
                'solidarites-lien-social'
            ],
            'Économie et vie des entreprises' => [
                'developpement-economique'
            ],
            'Politique de la ville' => [
                'urbanisme-logement-amenagement'
            ],
            'Solidarités - Santé - Égalités' => [
                'solidarites-lien-social'
            ],
            'Soutien et accompagnement des entreprises' => [
                'developpement-economique'
            ],
            'Transport et mobilité' => [
                'mobilite-transports'
            ],
            'Villes / Aménagements urbains' => [
                'urbanisme-logement-amenagement'
            ],
            'Économie sociale et solidaire' => [
                'solidarites-lien-social'
            ],
            'Éducation à l’environnement et au développement durable' => [
                'nature-environnement-risques'
            ],
        ];

        // l'autre partie est bien sur les catégories
        $mapping = [
            'Activités maritimes' => [
                'mers'
            ],
            'Agriculture et alimentation' => [
                'agriculture'
            ],
            'Agroalimentaire' => [
                'agriculture'
            ],
            'Alimentation' => [
                'agriculture'
            ],
            'Apprentissage' => [
                'education'
            ],
            'Biodiversité' => [
                'biodiversite'
            ],
            'Bois - Forêt' => [
                'biodiversite'
            ],
            'Citoyenneté et démocratie' => [
                'citoyennete'
            ],
            'Coopération européenne' => [
                'cooperation-transfrontaliere'
            ],
            'Coopération internationale' => [
                'cooperation-transfrontaliere'
            ],
            'Déchets et économie circulaire' => [
                'economie-circulaire'
            ],
            'Enseignement supérieur - Recherche' => [
                'education',
                'innovation-et-recherche'
            ],
            'Emploi' => [
                'emploi'
            ],
            'Enseignement supérieur' => [
                'education'
            ],
            'Europe et international' => [
                'cooperation-transfrontaliere'
            ],
            'Foncier' => [
                'foncier'
            ],
            'Formation' => [
                'education'
            ],
            'Formation - Orientation - Éducation' => [
                'education'
            ],
            'Handicap' => [
                'handicap'
            ],
            'Innovation' => [
                'innovation-et-recherche'
            ],
            'Logement' => [
                'logement-habitat'
            ],
            'Mer' => [
                'mers'
            ],
            'Montagne' => [
                'montagne'
            ],
            'Orientation' => [
                'education'
            ],
            'Plan littoral 21' => [
                'mers'
            ],
            'Plantations - Arboriculture' => [
                'agriculture'
            ],
            'Recherche' => [
                'innovation-et-recherche'
            ],
            'Santé' => [
                'sante'
            ],
            'Service civique' => [
                'citoyennete'
            ],
            'Sport' => [
                'sport'
            ],
            'Sûreté - Sécurité' => [
                'securite'
            ],
            'Tourisme' => [
                'tourisme'
            ],
            'Transformation numérique' => [
                'numerique'
            ],
            'Vie associative' => [
                'education'
            ],
            'Viticulture' => [
                'agriculture'
            ],
            'Économie touristique' => [
                'tourisme'
            ],
            'Éducation' => [
                'education'
            ],
            'Égalité femme-homme' => [
                'egalite-des-chances'
            ],
            'Égalités' => [
                'egalite-des-chances'
            ],
            'Élevage' => [
                'consommation-et-production'
            ],
            
        ];
        
        foreach ($categories as $categoryName) {
            $found = false;
            $categoryName = trim($categoryName);
            if (isset($mapping[$categoryName])) {
                foreach ($mapping[$categoryName] as $slugCategory) {
                    $category = $this->managerRegistry->getRepository(Category::class)->findOneBy([
                        'slug' => $slugCategory
                    ]);
                    if ($category instanceof Category) {
                        $found = true;
                        $aid->addCategory($category);
                    }
                }
            }

            if (isset($mappingThemes[$categoryName])) {
                foreach ($mappingThemes[$categoryName] as $slugCategoryTheme) {
                    $categoryTheme = $this->managerRegistry->getRepository(CategoryTheme::class)->findOneBy([
                        'slug' => $slugCategoryTheme
                    ]);
                    if ($categoryTheme instanceof CategoryTheme) {
                        $found = true;
                        foreach ($categoryTheme->getCategories() as $category) {
                            $aid->addCategory($category);
                        }
                    }
                }
            }
            if ($found) {
                $this->thematiquesOk[] = $categoryName;
            } else {
                $this->thematiquesKo[] = $categoryName;
            }
        }

        return $aid;
    }
}
