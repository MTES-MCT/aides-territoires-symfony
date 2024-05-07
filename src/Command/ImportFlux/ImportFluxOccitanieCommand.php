<?php

namespace App\Command\ImportFlux;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command\ImportFlux\ImportFluxCommand;
use App\Entity\Aid\Aid;
use App\Entity\Category\Category;

#[AsCommand(name: 'at:import_flux:occitanie', description: 'Import de flux Occitanie')]
class ImportFluxOccitanieCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux Occitanie';
    protected string $commandTextEnd = '>Import de flux Occitanie';

    protected ?string $importUniqueidPrefix = 'OCCITANIE_';
    protected ?int $idDataSource = 11;

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['recordid'])) {
            return null;
        }
        $importUniqueid = $this->importUniqueidPrefix . $aidToImport['recordid'];
        return substr($importUniqueid, 0, 200);
    }


    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        try {
            $timePublished = new \DateTime($aidToImport['fields']['date_publication'] ?? null);
        } catch (\Exception $e) {
            $timePublished = null;
        }
        try {
            $timeUpdate = new \DateTime($aidToImport['fields']['date_modification'] ?? null);
        } catch (\Exception $e) {
            $timeUpdate = null;
        }
        try {
            $timeCreate = new \DateTime($aidToImport['record_timestamp'] ?? null);
        } catch (\Exception $e) {
            $timeCreate = null;
        }

        $description = '';
        if (isset($aidToImport['fields']['chapo'])) {
            $description .= $this->htmlSanitizerInterface->sanitize($aidToImport['fields']['chapo']);
        }
        if (isset($aidToImport['fields']['description'])) {
            $description .= $this->htmlSanitizerInterface->sanitize($aidToImport['fields']['description']);
        }
        if (trim($description) == '') {
            $description = null;
        }

        return [
            'name' => isset($aidToImport['fields']['titre']) ? strip_tags($aidToImport['fields']['titre']) : null,
            'nameInitial' => isset($aidToImport['fields']['titre']) ? strip_tags($aidToImport['fields']['titre']) : null,
            'timePublished' => $timePublished,
            'description' => $description,
            'timeUpdate'=> $timeUpdate,
            'originUrl' => $aidToImport['fields']['url'] ?? null,
            'timeCreate' => $timeCreate,
            'importRawObjectCalendar' => null,
            'isCallForProject' => (isset($aidToImport['fields']['type']) && $aidToImport['fields']['type'] == 'Appels à projets') ? true : false,
            'contact' => "Pour contacter la Région Occitanie ou candidater à l'offre, veuillez cliquer sur le bouton 'Plus d'informations' ou sur le bouton 'Candidater à l'aide'.",
            'importDataMention' => 'Ces données sont mises à disposition par la Région Occitanie.',
        ];
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        $categories = (isset($aidToImport['fields']) && isset($aidToImport['fields']['thematiques']))
                    ? explode(',', $aidToImport['fields']['thematiques'])
                    : [];

        foreach ($categories as $category) {
            try {
                $category = $this->managerRegistry->getRepository(Category::class)->findOneBy(['name' => $category]);
                if (!$category instanceof Category) {
                    throw new \Exception('Impossible de charger la catégorie : ' . $category);
                }
                $aid->addCategory($category);
            } catch (\Exception $e) {
                continue;
            }
        }
        return $aid;
    }
}
