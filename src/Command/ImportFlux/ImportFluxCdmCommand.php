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
use App\Entity\Organization\OrganizationType;
use App\Entity\Reference\KeywordReference;
use App\Repository\Aid\AidDestinationRepository;
use App\Repository\Aid\AidStepRepository;

#[AsCommand(name: 'at:import_flux:cdm', description: 'Import de flux conseil départemental de la manche')]
class ImportFluxCdmCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux conseil départemental de la manche';
    protected string $commandTextEnd = '>Import de flux conseil départemental de la manche';

    protected ?string $importUniqueidPrefix = 'CDManche_';
    protected ?int $idDataSource = 12;

    /**
     * retourne un identifiant unique pour l'import
     *
     * @param array<mixed, mixed> $aidToImport
     * @return string|null
     */
    protected function getImportUniqueid(array $aidToImport): ?string
    {
        if (!isset($aidToImport['id'])) {
            return null;
        }
        return $this->importUniqueidPrefix . $aidToImport['id'];
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param array<mixed, mixed> $params
     * @return array<mixed, mixed>
     */
    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        $dateStart = $this->getDateTimeOrNull($aidToImport['start_date'] ?? null);
        $dateSubmissionDeadline = $this->getDateTimeOrNull($aidToImport['submission_deadline'] ?? null);

        $contact = '';
        if (isset($aidToImport['contact'])) {
            $contact .= $this->getCleanHtml($aidToImport['contact']);
        }
        if (isset($aidToImport['contact_phone'])) {
            $contact .= '  ' . $this->getCleanHtml($aidToImport['contact_phone']);
        }
        if (trim($contact) == '') {
            $contact = null;
        }

        $return = [
            'importDataMention' => 'Ces données sont mises à disposition par le Conseil départemental de la Manche.',
            'name' => isset($aidToImport['name'])
                ? strip_tags((string) $aidToImport['name']) : null,
            'nameInitial' => isset($aidToImport['name'])
                ? strip_tags((string) $aidToImport['name']) : null,
            'description' => isset($aidToImport['description'])
                ? $this->getCleanHtml($aidToImport['description']) : null,
            'originUrl' => isset($aidToImport['origin_url'])
                ? $this->getValidExternalUrlOrNull($aidToImport['origin_url']) : null,
            'applicationUrl' => isset($aidToImport['application_url'])
                ? $this->getValidExternalUrlOrNull($aidToImport['application_url']) : null,
            'isCallForProject' => isset($aidToImport['is_call_for_project'])
                ? $aidToImport['is_call_for_project'] : null,
            'dateStart' => $dateStart,
            'dateSubmissionDeadline' => $dateSubmissionDeadline,
            'eligibility' => isset($aidToImport['eligibility'])
                ? $this->getCleanHtml($aidToImport['eligibility']) : null,
            'contact' => $contact,
        ];

        // on ajoute les données brut d'import pour comparer avec les données actuelles
        return $this->mergeImportDatas($return);
    }

    /**
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setAidTypes(array $aidToImport, Aid $aid): Aid
    {
        // converti en tableau
        if (is_string($aidToImport['aid_types'])) {
            $aidToImport['aid_types'] = [$aidToImport['aid_types']];
        }

        foreach ($aidToImport['aid_types'] as $aidTypeName) {
            $aidType = $this->managerRegistry->getRepository(AidType::class)
                ->findOneBy(['name' => $aidTypeName]);
            if ($aidType instanceof AidType) {
                $aid->addAidType($aidType);
            }
        }

        return $aid;
    }

    /**
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['recurrence'])) {
            return $aid;
        }
        $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)
            ->findOneBy(['name' => $aidToImport['recurrence']]);
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

    /**
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
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
            $targetedAudience = $this->managerRegistry->getRepository(OrganizationType::class)
                ->findOneBy(['name' => $targetedAudienceName]);
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
            $category = $this->managerRegistry->getRepository(Category::class)
                ->findOneBy(['name' => $categoryName]);
            if ($category instanceof Category) {
                $aid->addCategory($category);
            }
        }
        return $aid;
    }

    /**
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setKeywords(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['categories']) || !is_array($aidToImport['categories'])) {
            return $aid;
        }
        foreach ($aidToImport['categories'] as $categoryName) {
            $keyword = $this->managerRegistry->getRepository(KeywordReference::class)
                ->findOneBy(['name' => $categoryName]);
            if ($keyword instanceof KeywordReference) {
                $aid->addKeywordReference($keyword);
            }
        }
        return $aid;
    }

    /**
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setAidDestinations(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['destinations']) || !is_array($aidToImport['destinations'])) {
            return $aid;
        }
        foreach ($aidToImport['destinations'] as $destinationName) {
            // on a un problème avec les apostrophes
            $destinationName = str_replace("'", "’", $destinationName);
            /** @var AidDestinationRepository $destinationRepository */
            $destinationRepository = $this->managerRegistry->getRepository(AidDestination::class);
            $destination = $destinationRepository->findOneBy(['name' => $destinationName]);
            if ($destination instanceof AidDestination) {
                $aid->addAidDestination($destination);
            }
        }
        return $aid;
    }
}
