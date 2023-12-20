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

#[AsCommand(name: 'at:import_flux:cddr', description: 'Import de flux conseil départemental de la drôme')]
class ImportFluxDepartementDromeCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux conseil départemental de la drôme';
    protected string $commandTextEnd = '>Import de flux conseil départemental de la drôme';

    protected ?string $importUniqueidPrefix = 'CDDr_';
    protected ?int $idDataSource = 9;

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
        $keys = ['start_date', 'predeposit_date', 'submission_deadline', 'recurrence'];

        $importRawObjectCalendar = [];
        foreach ($keys as $key) {
            if (isset($aidToImport[$key])) {
                $importRawObjectCalendar[$key] = $aidToImport[$key];
            }
        }
        if (count($importRawObjectCalendar) == 0) {
            $importRawObjectCalendar = null;
        }

        $importRawObject = $aidToImport;
        foreach ($keys as $key) {
            if (isset($importRawObject[$key])) {
                unset($importRawObject[$key]);
            }
        }

        $description = isset($aidToImport['description']) ? $aidToImport['description'] : null;
        if ($description) {
            if (strpos($description, 'Service Instructeur et Référent') !== false) {
                $description = substr($description, 0, strpos($description, 'Service Instructeur et Référent'));
            }
            $description = $this->htmlSanitizerInterface->sanitize($description);
        }

        $eligibility = isset($aidToImport['description']) ? $aidToImport['description'] : null;
        if ($eligibility) {
            if (strpos($eligibility, 'Opérations éligibles') !== false) {
                $eligibility = substr($eligibility, strpos($eligibility, 'Opérations éligibles'));
                if (strpos($eligibility, 'Type d') !== false) {
                    $eligibility = substr($eligibility, 0, strpos($eligibility, 'Type d'));
                }
                $eligibility = $this->htmlSanitizerInterface->sanitize($eligibility);
            } else {
                $eligibility = null;
            }
        }

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
        
        $contact = isset($aidToImport['contact']) ? $this->htmlSanitizerInterface->sanitize($aidToImport['contact']) : null;
        if (!$contact) {
            $contact = isset($aidToImport['description']) ? $aidToImport['description'] : null;
            if ($contact) {
                if (strpos($contact, 'Service Instructeur et Référent') !== false) {
                    $contact = substr($contact, strpos($contact, 'Service Instructeur et Référent'));
                    $contact = $this->htmlSanitizerInterface->sanitize($contact);
                } else {
                    $contact = null;
                }
            }
        }


        return [
            'importDataMention' => 'Ces données sont mises à disposition par le Conseil départemental de la Manche.',
            'importRawObjectCalendar' => $importRawObjectCalendar,
            'importRawObject' => $importRawObject,
            'name' => isset($aidToImport['name']) ? strip_tags($aidToImport['name']) : null,
            'nameInitial' => isset($aidToImport['name']) ? strip_tags($aidToImport['name']) : null,
            'description' => $description,
            'eligibility' => $eligibility,
            'originUrl' => isset($aidToImport['origin_url']) ? $aidToImport['origin_url'] : null,
            'applicationUrl' => isset($aidToImport['application_url']) ? $aidToImport['application_url'] : null,
            'isCallForProject' => isset($aidToImport['is_call_for_project']) ? $aidToImport['is_call_for_project'] : false,
            'dateStart' => $dateStart,
            'dateSubmissionDeadline' => $dateSubmissionDeadline,
            'contact' => $contact
        ];
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

    protected function setAidSteps(array $aidToImport, Aid $aid): Aid
    {
        $aidSteps = $this->managerRegistry->getRepository(AidStep::class)->findCustom([
            'slugs' => [
                AidStep::SLUG_PREOP,
                AidStep::SLUG_OP,
            ]
        ]);
        foreach ($aidSteps as $aidStep) {
            $aid->addAidStep($aidStep);
        }
        return $aid;
    }
}