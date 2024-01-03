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
        if (!isset($aidToImport['id'])) {
            return null;
        }
        $importUniqueid = $this->importUniqueidPrefix . $aidToImport['id'];
        return $importUniqueid;
    }

    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        $importRaws = $this->getImportRaws($aidToImport);
        $importRawObjectCalendar = $importRaws['importRawObjectCalendar'];
        $importRawObject = $importRaws['importRawObject'];

        $dateStart = null;
        try {
            $dateStart = new \DateTime($aidToImport['start_date'] ?? null);
        } catch (\Exception $e) {
            $dateStart = null;
        }

        $dateSubmissionDeadline = null;
        try {
            $dateSubmissionDeadline = new \DateTime($aidToImport['submission_deadline'] ?? null);
        } catch (\Exception $e) {
            $dateSubmissionDeadline = null;
        }

        $contact = '';
        if (isset($aidToImport['contact'])) {
            $contact .= $this->htmlSanitizerInterface->sanitize($aidToImport['contact']);
        }
        if (isset($aidToImport['contact_phone'])) {
            $contact .= '  ' . $this->htmlSanitizerInterface->sanitize($aidToImport['contact_phone']);
        }
        if (trim($contact) == '') {
            $contact = null;
        }

        return [
            'importDataMention' => 'Ces données sont mises à disposition par le Conseil départemental de la Manche.',
            'importRawObjectCalendar' => $importRawObjectCalendar,
            'importRawObject' => $importRawObject,
            'name' => isset($aidToImport['name']) ? strip_tags($aidToImport['name']) : null,
            'nameInitial' => isset($aidToImport['name']) ? strip_tags($aidToImport['name']) : null,
            'description' => isset($aidToImport['description']) ? $this->htmlSanitizerInterface->sanitize($aidToImport['description']) : null,
            'originUrl' => isset($aidToImport['origin_url']) ? $aidToImport['origin_url'] : null,
            'applicationUrl' => isset($aidToImport['application_url']) ? $aidToImport['application_url'] : null,
            'isCallForProject' => isset($aidToImport['is_call_for_project']) ? $aidToImport['is_call_for_project'] : null,
            'dateStart' => $dateStart,
            'dateSubmissionDeadline' => $dateSubmissionDeadline,
            'eligibility' => isset($aidToImport['eligibility']) ? $this->htmlSanitizerInterface->sanitize($aidToImport['eligibility']) : null,
            'contact' => $contact,

        ];
    }

    protected function setAidTypes(array $aidToImport, Aid $aid): Aid
    {
        // converti en tableau
        if (is_string($aidToImport['aid_types'])) {
            $aidToImport['aid_types'] = [$aidToImport['aid_types']];
        }

        foreach ($aidToImport['aid_types'] as $aidTypeName) {
            $aidType = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['name' => $aidTypeName]);
            if ($aidType instanceof AidType) {
                $aid->addAidType($aidType);
            }
        }

        return $aid;
    }

    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['recurrence'])) {
            return $aid;
        }
        $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['name' => $aidToImport['recurrence']]);
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

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['targeted_audiences']) || !is_array($aidToImport['targeted_audiences'])) {
            return $aid;
        }

        foreach ($aidToImport['targeted_audiences'] as $targetedAudienceName) {
            // Mostly matches our audiences save for two, so mapping manually here
            if ($targetedAudienceName == 'Établissements publics (écoles, bibliothèques…)') {
                $targetedAudienceName = 'Établissement public';
            } elseif ($targetedAudienceName == 'EPCI à fiscalité propre') {
                $targetedAudienceName = 'Intercommunalité / Pays';
            }
            $targetedAudience = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy(['name' => $targetedAudienceName]);
            if ($targetedAudience instanceof OrganizationType) {
                $aid->addAidAudience($targetedAudience);
            }
        }
        return $aid;
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['categories']) || !is_array($aidToImport['categories'])) {
            return $aid;
        }
        foreach ($aidToImport['categories'] as $categoryName) {
            $category = $this->managerRegistry->getRepository(Category::class)->findOneBy(['name' => $categoryName]);
            if ($category instanceof Category) {
                $aid->addCategory($category);
            }
        }
        return $aid;
    }

    protected function setKeywords(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['categories']) || !is_array($aidToImport['categories'])) {
            return $aid;
        }
        foreach ($aidToImport['categories'] as $categoryName) {
            $keyword = $this->managerRegistry->getRepository(Keyword::class)->findOneBy(['name' => $categoryName]);
            if ($keyword instanceof Keyword) {
                $aid->addKeyword($keyword);
            }
        }
        return $aid;
    }

    protected function setAidDestinations(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['destinations']) || !is_array($aidToImport['destinations'])) {
            return $aid;
        }
        foreach($aidToImport['destinations'] as $destinationName) {
            // on a un problème avec les apostrophes
            $destinationName = preg_replace("/'/", "’", $destinationName);
            $destination = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy(['name' => $destinationName]);
            if ($destination instanceof AidDestination) {
                $aid->addAidDestination($destination);
            }
        }
        return $aid;
    }
}