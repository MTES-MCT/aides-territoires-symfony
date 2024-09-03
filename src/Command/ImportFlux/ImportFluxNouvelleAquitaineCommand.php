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
            'name' => isset($aidToImport['title']) ? strip_tags($aidToImport['title']) : null,
            'nameInitial' => isset($aidToImport['title']) ? strip_tags($aidToImport['title']) : null,
            'description' => $description,
            'originUrl' => isset($aidToImport['guid']) ? $aidToImport['guid'] : null,
            'dateStart' => $dateStart,
        ];

        // on ajoute les données brut d'import pour comparer avec les données actuelles
        return $this->mergeImportDatas($return);
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['category'])) {
            return $aid;
        }

        $mapping = [
            'Aménagement numérique' => [
                'numerique',
                'inclusion-numerique'
            ],
            'Économie territoriale' => [
                'circuits-courts-filieres'
            ],
            'Égalité' => [
                'egalite-des-chances'
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

        return $aid;
    }

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid
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
            if (preg_match('/.*' . $key . '.*/i', $aidToImport['Pour qui'])) {
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
}
