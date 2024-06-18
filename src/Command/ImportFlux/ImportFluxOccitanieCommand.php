<?php

namespace App\Command\ImportFlux;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command\ImportFlux\ImportFluxCommand;
use App\Entity\Aid\Aid;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;

#[AsCommand(name: 'at:import_flux:occitanie', description: 'Import de flux Occitanie')]
class ImportFluxOccitanieCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux Occitanie';
    protected string $commandTextEnd = '>Import de flux Occitanie';

    protected ?string $importUniqueidPrefix = 'OCCITANIE_';
    protected ?int $idDataSource = 11;
    protected bool $paginationEnabled = true;
    protected int $nbByPages = 100;

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['_id'])) {
            return null;
        }
        $importUniqueid = $this->importUniqueidPrefix . $aidToImport['_id'];
        return substr($importUniqueid, 0, 200);
    }


    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        try {
            $timePublished = new \DateTime($aidToImport['date_publication'] ?? null);
        } catch (\Exception $e) {
            $timePublished = null;
        }
        try {
            $timeUpdate = new \DateTime($aidToImport['date_modification'] ?? null);
        } catch (\Exception $e) {
            $timeUpdate = null;
        }

        return [
            'name' => isset($aidToImport['titre']) ? html_entity_decode(strip_tags($aidToImport['titre']), ENT_QUOTES, 'UTF-8') : null,
            'nameInitial' => isset($aidToImport['titre']) ? html_entity_decode(strip_tags($aidToImport['titre']), ENT_QUOTES, 'UTF-8') : null,
            'timePublished' => $timePublished,
            'timeUpdate'=> $timeUpdate,
            'description' => $this->concatHtmlFields($aidToImport, ['chapo', 'introduction']),
            'originUrl' => $aidToImport['url'] ?? null,
            'importRawObjectCalendar' => null,
            'isCallForProject' => (isset($aidToImport['type']) && $aidToImport['type'] == 'Appels à projets') ? true : false,
            'importDataMention' => 'Ces données sont mises à disposition par la Région Occitanie.',
        ];
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        $categories = (isset($aidToImport['thematiques']) && $aidToImport['thematiques'])
                    ? explode(',', $aidToImport['thematiques'])
                    : [];

        // une partie du mapping à été fait sur les CategoryTheme et non sur les catégories
        $mappingThemes = [
            'Culture' => [
                'culture-patrimoine-sports-tourisme',
            ],
            'Environnement - Climat' => [
                'nature-environnement-risques'
            ],
            'Solidarités - Santé - Égalités' => [
                'solidarites-lien-social'
            ],
            'Aménagement du territoire' => [
                'urbanisme-logement-amenagement'
            ],
            'Économie et vie des entreprises' => [
                'developpement-economique'
            ],
            'Transport et mobilité' => [
                'mobilite-transports'
            ]
        ];

        // l'autre partie est bien sur les catégories
        $mapping = [
            'Citoyenneté et démocratie' => [
                'citoyennete'
            ],
            'Enseignement supérieur - Recherche' => [
                'education',
                'innovation-et-recherche'
            ],
            'Europe et international' => [
                'cooperation-transfrontaliere'
            ],
            'Agriculture et alimentation' => [
                'agriculture'
            ],
            'Formation - Orientation - Éducation' => [
                'education'
            ],
            'Sport' => [
                'sport'
            ],
            'Mer' => [
                'mers'
            ],
            'Montagne' => [
                'montagne'
            ],
            'Tourisme' => [
                'tourisme'
            ],
            'Sûreté - Sécurité' => [
                'securite'
            ]
            
        ];
        
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

            if (isset($mappingThemes[$categoryName])) {
                foreach ($mappingThemes[$categoryName] as $slugCategoryTheme) {
                    $categoryTheme = $this->managerRegistry->getRepository(CategoryTheme::class)->findOneBy([
                        'slug' => $slugCategoryTheme
                    ]);
                    if ($categoryTheme instanceof CategoryTheme) {
                        foreach ($categoryTheme->getCategories() as $category) {
                            $aid->addCategory($category);
                        }
                    }
                }
            }
        }

        return $aid;
    }
}
