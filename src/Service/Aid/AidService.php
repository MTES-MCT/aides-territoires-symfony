<?php

namespace App\Service\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidInstructor;
use App\Entity\Aid\AidLock;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AidService
{
    public function __construct(
        protected HttpClientInterface $httpClientInterface,
        protected UserService $userService,
        protected RouterInterface $routerInterface,
        protected ReferenceService $referenceService,
        protected ManagerRegistry $managerRegistry
    )
    {
        
    }

    public function getLock(Aid $aid): ?AidLock
    {
        $aidLocks = $this->managerRegistry->getRepository(AidLock::class)->findBy(['aid' => $aid]);
        foreach ($aidLocks as $aidLock) {
            return $aidLock;
        }

        return null;
    }
    public function isLockedByAnother(Aid $aid, User $user): bool
    {
        $aidLocks = $this->managerRegistry->getRepository(AidLock::class)->findBy(['aid' => $aid]);
        foreach ($aidLocks as $aidLock) {
            if ($aidLock->getUser() != $user) {
                return true;
            }
        }
        return false;
    }

    public function isLocked(Aid $aid): bool
    {
        $aidLocks = $this->managerRegistry->getRepository(AidLock::class)->findBy(['aid' => $aid]);
        return count($aidLocks) > 0;
    }
    
    public function lockAid(Aid $aid, User $user): void
    {
        try {
            // vérifie que l'aide n'est pas déjà lock
            $aidLocks = $this->managerRegistry->getRepository(AidLock::class)->findBy(['aid' => $aid]);

            if (count($aidLocks) == 0) {
                $aidLock = new AidLock();
                $aidLock->setAid($aid);
                $aidLock->setUser($user);
                $this->managerRegistry->getManager()->persist($aidLock);
                $this->managerRegistry->getManager()->flush();
            }
        } catch (\Exception $e) {
        }
    }

    public function unlockAid(Aid $aid): void
    {
        $aidLocks = $this->managerRegistry->getRepository(AidLock::class)->findBy(['aid' => $aid]);
        foreach ($aidLocks as $aidLock) {
            $this->managerRegistry->getManager()->remove($aidLock);
        }
        $this->managerRegistry->getManager()->flush();
    }

    public function duplicateAid(?Aid $aid, ?User $user): Aid
    {
        if (!$aid instanceof Aid) {
            return new Aid();
        }

        // nouvel auteur ou ancien
        $aidUser = $user instanceof User ? $user : $aid->getAuthor();

        $newAid = new Aid();
        $newAid->setName($aid->getName());
        // le slug est automatique
        $newAid->setDescription($aid->getDescription());
        $newAid->setStatus(Aid::STATUS_DRAFT);
        $newAid->setOriginUrl($aid->getOriginUrl());
        foreach ($aid->getAidAudiences() as $aidAudience) {
            $newAid->addAidAudience($aidAudience);
        }
        foreach ($aid->getAidTypes() as $aidType) {
            $newAid->addAidType($aidType);
        }
        foreach ($aid->getAidDestinations() as $aidDestination) {
            $newAid->addAidDestination($aidDestination);
        }
        $newAid->setDateStart($aid->getDateStart());
        $newAid->setDatePredeposit($aid->getDatePredeposit());
        $newAid->setDateSubmissionDeadline($aid->getDateSubmissionDeadline());
        $newAid->setContactEmail($aid->getContactEmail());
        $newAid->setContactPhone($aid->getContactPhone());
        $newAid->setContactDetail($aid->getContactDetail());
        $newAid->setAuthor($aidUser);
        foreach ($aid->getAidSteps() as $aidStep) {
            $newAid->addAidStep($aidStep);
        }
        $newAid->setEligibility($aid->getEligibility());
        $newAid->setAidRecurrence($aid->getAidRecurrence());
        $newAid->setPerimeter($aid->getPerimeter());
        $newAid->setApplicationUrl($aid->getApplicationUrl());
        // foce à false
        $newAid->setIsImported(false);
        // force a null
        $newAid->setImportUniqueid(null);
        $newAid->setFinancerSuggestion($aid->getFinancerSuggestion());
        $newAid->setImportDataUrl($aid->getImportDataUrl());
        $newAid->setDateImportLastAccess($aid->getDateImportLastAccess());
        $newAid->setImportShareLicence($aid->getImportShareLicence());
        $newAid->setIsCallForProject($aid->isIsCallForProject());
        $newAid->setAmendedAid($aid->getAmendedAid());
        $newAid->setIsAmendment($aid->isIsAmendment());
        $newAid->setAmendmentAuthorName($aid->getAmendmentAuthorName());
        $newAid->setAmendmentComment($aid->getAmendmentComment());
        $newAid->setAmendmentAuthorEmail($aid->getAmendmentAuthorEmail());
        $newAid->setAmendmentAuthorOrg($aid->getAmendmentAuthorOrg());
        $newAid->setSubventionRateMin($aid->getSubventionRateMin());
        $newAid->setSubventionRateMax($aid->getSubventionRateMax());
        $newAid->setSubventionComment($aid->getSubventionComment());
        $newAid->setContact($aid->getContact());
        $newAid->setInstructorSuggestion($aid->getInstructorSuggestion());
        $newAid->setProjectExamples($aid->getProjectExamples());
        $newAid->setPerimeterSuggestion($aid->getPerimeterSuggestion());
        $newAid->setShortTitle($aid->getShortTitle());
        $newAid->setInFranceRelance($aid->isInFranceRelance());
        $newAid->setGenericAid($aid->getGenericAid());
        $newAid->setLocalCharacteristics($aid->getLocalCharacteristics());
        $newAid->setImportDataSource($aid->getImportDataSource());
        $newAid->setEligibilityTest($aid->getEligibilityTest());
        $newAid->setIsGeneric(false);
        $newAid->setImportRawObject($aid->getImportRawObject());
        $newAid->setLoanAmount($aid->getLoanAmount());
        $newAid->setOtherFinancialAidComment($aid->getOtherFinancialAidComment());
        $newAid->setRecoverableAdvanceAmount($aid->getRecoverableAdvanceAmount());
        $newAid->setNameInitial($aid->getNameInitial());
        $newAid->setAuthorNotification($aid->isAuthorNotification());
        $newAid->setImportRawObjectCalendar($aid->getImportRawObjectCalendar());
        $newAid->setImportRawObjectTemp($aid->getImportRawObjectTemp());
        $newAid->setImportRawObjectTempCalendar($aid->getImportRawObjectTempCalendar());
        $newAid->setEuropeanAid($aid->getEuropeanAid());
        $newAid->setImportDataMention($aid->getImportDataMention());
        $newAid->setHasBrokenLink($aid->isHasBrokenLink());
        $newAid->setIsCharged($aid->isIsCharged());
        $newAid->setImportUpdated($aid->isImportUpdated());
        $newAid->setDsId($aid->getDsId());
        $newAid->setDsMapping($aid->getDsMapping());
        $newAid->setDsSchemaExists($aid->isDsSchemaExists());
        $newAid->setContactInfoUpdated($aid->isContactInfoUpdated());
        // on ne reprends pas timePublished
        foreach ($aid->getCategories() as $category) {
            $newAid->addCategory($category);
        }
        foreach ($aid->getProjectReferences() as $projectReference) {
            $newAid->addProjectReference($projectReference);
        }
        foreach ($aid->getKeywords() as $keyWord) {
            $newAid->addKeyword($keyWord);
        }
        foreach ($aid->getPrograms() as $program) {
            $newAid->addProgram($program);
        }
        foreach ($aid->getAidFinancers() as $aidFinancer) {
            $newAidFinancer = new AidFinancer();
            $newAidFinancer->setBacker($aidFinancer->getBacker());
            $newAidFinancer->setPosition($aidFinancer->getPosition());
            $newAid->addAidFinancer($newAidFinancer);
        }
        foreach ($aid->getAidInstructors() as $aidInstructor) {
            $newAidInstructor = new AidInstructor();
            $newAidInstructor->setBacker($aidInstructor->getBacker());
            $newAidInstructor->setPosition($aidInstructor->getPosition());
            $newAid->addAidInstructor($newAidInstructor);
        }
        // on ne reprends pas aidProjects
        // on ne reprends pas aidSuggestedAidProjects
        foreach ($aid->getBundles() as $bundle) {
            $newAid->addBundle($bundle);
        }
        foreach ($aid->getExcludedSearchPages() as $excludedSearchPage) {
            $newAid->addExcludedSearchPage($excludedSearchPage);
        }
        foreach ($aid->getHighlightedSearchPages() as $highlitedSearchPage) {
            $newAid->addHighlightedSearchPage($highlitedSearchPage);
        }
        // on ne reprends pas tous les logs

        return $newAid;
    }

    public function canUserAccessStatsPage(?User $user, Aid $aid) : bool
    {
        if (!$user instanceof User || !$aid instanceof Aid) {
            return false;
        }

        $access = false;
        if(
            $aid->getAuthor() == $user
            || $this->userService->isUserGranted($user, User::ROLE_ADMIN)
            || (
                $aid->getOrganization()
                && $this->userService->isMemberOfOrganization($aid->getOrganization(), $user)
            )
        ) {
            $access = true;
        }

        return $access;
        
    }

    public function searchAids(array $aidParams): array
    {
        $aids = $this->managerRegistry->getRepository(Aid::class)->findCustom($aidParams);

        if (!isset($aidParams['noRelaunch']) && !isset($params['notRelaunch'])) {
            if (count($aids) <= 10) {
                $aidParams['scoreTotalMin'] = 1;
                $aidParams['scoreObjectsMin'] = 0;
                $aids = $this->managerRegistry->getRepository(Aid::class)->findCustom($aidParams);
            }
        }

        if (!isset($aidParams['noPostPopulate']) && !isset($params['notPostPopulate'])) {
            $aids = $this->postPopulateAids($aids, $aidParams);
        }

        return $aids;
    }

    public function extractInlineStyles(Aid $aid): Aid
    {
        $styles = [];
        $dom = new \DOMDocument();
        $dom->loadHTML($aid->getDescription());

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//*[@style]");
        foreach ($nodes as $node) {
            $itemId = $node->getAttribute('id') ?? '';
            if ($itemId == '') {
                $itemId = uniqid('style-');
                $node->setAttribute('id', $itemId);
            }
            $styles[$itemId] = $node->nodeValue;
        }

        // Sélectionner uniquement le contenu intérieur de la balise <body>
        $body = $xpath->query('//body')->item(0);
        $newHtml = '';
        foreach ($body->childNodes as $childNode) {
            $newHtml .= $dom->saveHTML($childNode);
        }

        $aid->setDescription($newHtml);
        // $aid->setInlineStyles($styles);
        return $aid;
    }



    public function postPopulateAids(array $aids, ?array $params) : array
    {
        // on déduplique les génériques
        $aids = $this->unDuplicateGenerics($aids, $params['perimeterFrom'] ?? null);

        // pour les portails il y a des aides mises en avant et des aides à exclures
        $aids = $this->handleSearchPageRules($aids, $params);
        
        return $aids;
    }

    // pour les portails il y a des aides mises en avant et des aides à exclures
    public function handleSearchPageRules(array $aids, $params): array
    {
        if (isset($params['searchPage']) && $params['searchPage'] instanceof SearchPage) {
            // aides à exclures
            foreach ($aids as $key => $aid) {
                if ($params['searchPage']->getExcludedAids()->contains($aid)) {
                    unset($aids[$key]);
                }
            }

            // aides à mettre en avant
            $highlightedAids = [];
            foreach ($params['searchPage']->getHighlightedAids() as $aid) {
                if ($aid->isLive()) {
                    $highlightedAids[] = $aid;
                }
            }
            $normalAids = [];
            foreach ($aids as $key => $aid) {
                if (!$params['searchPage']->getHighlightedAids()->contains($aid)) {
                    $normalAids[] = $aid;
                }
                unset($aids[$key]);
            }

            // on ajoute les normalAids après les highlightAids
            $aids = array_values(array_merge($highlightedAids, $normalAids));
        }

        return $aids;
    }

    /*
        Nous ne devrions jamais avoir à la fois l'aide générique et sa version locale dans les résultats de recherche.
        Lequel devrait être supprimé des résultats dépend de plusieurs facteurs.
        Nous prenons en compte le périmètre d'échelle associé à l'aide locale.

        Lorsque la recherche porte sur une zone plus large que le périmètre de l'aide locale,
            nous affichons la version générique.
        Lorsque la recherche porte sur une zone plus petite que le périmètre de l'aide locale,
            nous affichons la version locale.
    */
    public function unDuplicateGenerics(array $aids, ?Perimeter $perimeter) : array
    {
        // Si on n'a pas de périmètre de recherche
        if (!$perimeter instanceof Perimeter) {
            $searchSmaller = false;
            $searchWider = true;
        }
        // converti le array en ArrayCollection
        $aids = new ArrayCollection($aids);

        // les aides que l'on va exclude
        $perimeterSearch = $perimeter instanceof Perimeter;
        $perimeterScale = ($perimeter instanceof Perimeter) ? $perimeter->getScale() : 0;
        // Parcours la liste des aides actuelles
        /** @var Aid $aid */
        foreach ($aids as $keyAid => $aid) {
            // Si on a un périmètre de recherche
            if ($perimeterSearch) {
                $searchSmaller = $perimeterScale <= $aid->getPerimeter()->getScale();
                $searchWider = $perimeterScale > $aid->getPerimeter()->getScale();
            }

            if ($searchSmaller) {
                // si c'est une aide generic avec des declinaisons, on la retire si un des aides locales est dans la liste
                if ($aid->getAidsFromGeneric()) {
                    $localInList = false;
                    foreach ($aid->getAidsFromGeneric() as $aidFromGeneric) {
                        if ($aids->contains($aidFromGeneric)) {
                            $localInList = true;
                        }
                    }
                    if ($localInList) {
                        $aids->remove($aid);
                    }
                }
            } else if ($searchWider) {
                // Si c'est une aide locale et que la liste contiens l'aide générique, on la retire de la listes
                if ($aid->getGenericAid() && $aids->contains($aid->getGenericAid())) {
                    $aids->remove($keyAid);
                }
            }
        }

        return $aids->toArray();
    }

    public function getUrl(Aid $aid, $interface = UrlGeneratorInterface::ABSOLUTE_URL) : ?string {
        try {
            return $this->routerInterface->generate('app_aid_aid_details', ['slug' => $aid->getSlug()], $interface);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function userCanExportPdf(Aid $aid, ?User $user) : bool {
        if (!$user) {
            return false;
        }
        if ($user->getId() == $aid->getAuthor()->getId() || $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
            return true;
        }
        if ($user && $aid->getOrganization() && $this->userService->isMemberOfOrganization($aid->getOrganization(), $user)) { // le user fait parti de l'organization de l'aide
            return true;
        }
        return false;
    }

    public function userCanSee(Aid $aid, ?User $user) : bool {
        if (!$aid->isPublished()) {
            if ($user && $aid->getAuthor() && ($user->getId() == $aid->getAuthor()->getId())) { // c'est l'auteur
                return true;
            } else if ($user && $aid->getOrganization() && $this->userService->isMemberOfOrganization($aid->getOrganization(), $user)) { // le user fait parti de l'organization de l'aide
                return true;
            } else if ($user && $this->userService->isUserGranted($user, User::ROLE_ADMIN)) { // c'est un admin
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function userCanEdit(Aid $aid, ?User $user) : bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // si c'est l'auteur ou un admin
        if ($aid->getAuthor() == $user || $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
            return true;
        }

        // si il appartiens à l'organisation et peu editer les aides
        if ($aid->getOrganization()) {
            foreach ($aid->getOrganization()->getOrganizationAccesses() as $organizationAccess) {
                if ($organizationAccess->getUser() == $user && $organizationAccess->isEditAid()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function userCanDuplicate(Aid $aid, ?User $user) : bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // si c'est l'auteur ou un admin
        if ($aid->getAuthor() == $user || $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
            return true;
        }

        return false;
    }

    /**
     * Recupère les données chez Démarche Simplifiée (DS)
     *
     * @param integer $dsId
     * @param array $dsMapping
     * @param User|null $user
     * @param Organization|null $organization
     * @return array
     */
    public function getDatasFromDs(Aid $aid, ?User $user, ?Organization $organization): array
    {
        $datas = [
            'prepopulate_application_url' => false,
            'ds_folder_id' => false,
            'ds_folder_number' => false,
            'ds_application_url' => false
        ];
        // l'aide n'as pas de mapping DS
        if (!$aid->getDsMapping()) {
            return $datas;
        }
        
        // utilisateur non connecté
        if (!$user) {
            $datas['ds_application_url'] = true;
            return $datas;
        }

        $organizationType = ($user->getDefaultOrganization() && $user->getDefaultOrganization()->getOrganizationType()) ? $user->getDefaultOrganization()->getOrganizationType() : null;
        if (in_array($organizationType->getSlug(), [OrganizationType::SLUG_COMMUNE, OrganizationType::SLUG_EPCI])) {
            try {
                $response = $this->postPrepopulateData($aid->getDsId(), $aid->getDsMapping(), $user, $organization);
                $content = json_decode($response->getContent());

                $datas['prepopulate_application_url'] = $content->dossier_url ?? null;
                $datas['ds_folder_id'] = $content->dossier_id ?? null;
                $datas['ds_folder_number'] = $content->dossier_number ?? null;

            } catch (\Exception $e) {
                
            }
        }
        
        return $datas;
    }

    /**
     * Aoppel l'API Démarche Simplifiée (DS)
     *
     * @param integer $dsId
     * @param array $dsMapping
     * @param UserInterface|null $user
     * @param Organization|null $organization
     * @return void
     */
    public function postPrepopulateData(int $dsId, array $dsMapping, ?UserInterface $user, ?Organization $organization): mixed
    {
        $datas = $this->prepopulateDsFolder($dsMapping, $user, $organization);

        $response = $this->httpClientInterface->request(
            'POST',
            'https://www.demarches-simplifiees.fr/api/public/v1/demarches/'.$dsId.'/dossiers',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $datas
            ]
        );

        return $response;
    }

    /**
     * Fait le tableau de données à envoyer à Démarche Simplifiée (DS)
     *
     * @param array $dsMapping
     * @param UserInterface|null $user
     * @param Organization|null $organization
     * @return array
     */
    public function prepopulateDsFolder(array $dsMapping, ?UserInterface $user, ?Organization $organization): array
    {
        $datas = [];

        try {
            foreach ($dsMapping['FieldsList'] as $field) {
                if (isset($field['response_value']) && !empty($field['response_value'])) {
                    $datas[$field['ds_field_id']] = $field['response_value'];
                } else if (
                    isset($field['at_model']) && !empty($field['at_model'])
                    && isset($field['at_model_attr']) && !empty($field['at_model_attr'])
                ) {
                    switch ($field['at_model']) {
                        case 'User':
                            $value = $this->getFieldValue($field['at_model_attr'], $user);
                            break;


                        case 'Organization':
                            $value = $this->getFieldValue($field['at_model_attr'], $organization);
                            break;
                    }
                    if ($value) {
                        $datas[$field['ds_field_id']] = $value;
                    }
                }
            }

            return $datas;
        } catch (\Exception $e) {
            return $datas;
        }
    }

    /**
     * Recupère la donnée en fonction de l'entité et du champ
     * basé sur les nom de champ Django
     *
     * @param string $oldField
     * @param mixed $entity
     * @return string|null
     */
    private function getFieldValue(string $oldField, mixed $entity): ?string
    {
        if (!$entity) {
            return null;
        }

        if ($entity instanceof User) {
            switch ($oldField) {
                case 'last_name':
                    return $entity->getLastname();
                break;

                case 'first_name':
                    return $entity->getFirstname();
                break;

                case 'email':
                    return $entity->getEmail();
                break;
            }
        } else if ($entity instanceof Organization) {
            switch ($oldField) {
                case 'organizationType':
                    return $entity->getOrganizationType() ? $entity->getOrganizationType()->getName() : null;
                break;
            }
        }

        return null;
    }
}