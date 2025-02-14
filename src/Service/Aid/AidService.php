<?php

namespace App\Service\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidInstructor;
use App\Entity\Aid\AidLock;
use App\Entity\Backer\Backer;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Repository\Aid\AidRepository;
use App\Repository\Reference\KeywordReferenceRepository;
use App\Service\Api\VappApiService;
use App\Service\Log\LogAidApplicationUrlClickService;
use App\Service\Log\LogAidOriginUrlClickService;
use App\Service\Log\LogAidViewService;
use App\Service\User\UserService;
use App\Service\Various\StringService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use League\HTMLToMarkdown\HtmlConverter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AidService // NOSONAR too complex
{
    public function __construct(
        private HttpClientInterface $httpClientInterface,
        private UserService $userService,
        private RouterInterface $routerInterface,
        private ManagerRegistry $managerRegistry,
        private LoggerInterface $loggerInterface,
        private TagAwareCacheInterface $cache,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Retourne des projets référents suggérés pour une aide.
     *
     * @return array<int, ProjectReference>
     */
    public function getSuggestedProjectReferences(Aid $aid): array
    {
        $projectReferencesSuggestions = [];
        $projectReferenceRepository = $this->managerRegistry->getRepository(ProjectReference::class);
        $projectReferences = $projectReferenceRepository->findBy(
            [],
            ['name' => 'ASC']
        );

        // Pour chaque projet référent on faire une recherche pour voir si l'aide sort
        foreach ($projectReferences as $projectReference) {
            $aids = $this->searchAidsV3(
                [
                    'id' => $aid->getId(),
                    'keyword' => $projectReference->getName(),
                ]
            );

            if (!empty($aids)) {
                $projectReferencesSuggestions[] = $projectReference;
            }
        }

        return $projectReferencesSuggestions;
    }

    /**
     * Recherche des aides qui semble dupliquées.
     *
     * @return array<int, Aid>
     */
    public function getAidDuplicates(Aid $aid): array
    {
        if (!$aid->getOriginUrl()) {
            return [];
        }

        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        return $aidRepository->findCustom(
            [
                'originUrl' => $aid->getOriginUrl(),
                'exclude' => $aid,
                'perimeter' => $aid->getPerimeter() ?? null,
                'showInSearch' => true,
            ]
        );
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

    public function canUserAccessStatsPage(?User $user, Aid $aid): bool
    {
        if (!$user instanceof User || !$aid instanceof Aid) {
            return false;
        }

        $access = false;
        if (
            $aid->getAuthor() == $user
            || $this->userService->isUserGranted($user, User::ROLE_ADMIN)
            || ($aid->getOrganization() && $aid->getOrganization()->getBeneficiairies()->contains($user))
        ) {
            $access = true;
        }

        return $access;
    }

    public function extractInlineStyles(Aid $aid): Aid
    {
        $styles = [];
        $dom = new \DOMDocument();
        $dom->loadHTML($aid->getDescription());

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@style]');
        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            $itemId = $node->getAttribute('id');
            if ('' == $itemId) {
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

        return $aid;
    }

    /**
     * Traitement du tableau d'aides après la recherche.
     *
     * @param array<int, Aid>           $aids
     * @param array<string, mixed>|null $params
     *
     * @return array<int, Aid>
     */
    public function postPopulateAids(array $aids, ?array $params): array
    {
        // on déduplique les génériques
        $aids = $this->unDuplicateGenerics($aids, $params['perimeterFrom'] ?? null);

        // pour les portails il y a des aides mises en avant et des aides à exclures
        $aids = $this->handleSearchPageRules($aids, $params);

        return $aids;
    }

    /**
     * pour les portails il y a des aides mises en avant et des aides à exclures.
     *
     * @param array<int, Aid>           $aids
     * @param array<string, mixed>|null $params
     *
     * @return array<int, Aid>
     */
    public function handleSearchPageRules(array $aids, ?array $params): array // NOSONAR too complex
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
                    $ids[] = $aid->getId();
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
    /**
     * @param array<int, Aid> $aids
     *
     * @return array<int, Aid>
     */
    public function unDuplicateGenerics(array $aids, ?Perimeter $perimeter): array // NOSONAR too complex
    {
        // Si on n'a pas de périmètre de recherche
        if (!$perimeter instanceof Perimeter) {
            $searchSmaller = false;
            $searchWider = true;
        } else {
            $searchSmaller = false;
            $searchWider = false;
        }
        // converti le array en ArrayCollection
        $aids = new ArrayCollection($aids);

        // les aides que l'on va exclude
        $perimeterSearch = $perimeter instanceof Perimeter;
        $perimeterScale = ($perimeter instanceof Perimeter) ? $perimeter->getScale() : 0;

        // Parcours la liste des aides actuelles
        /** @var Aid $aid */
        foreach ($aids as $aid) {
            // Si on a un périmètre de recherche
            if ($perimeterSearch && $aid->getPerimeter()) {
                $searchSmaller = $perimeterScale <= $aid->getPerimeter()->getScale();
                $searchWider = $perimeterScale > $aid->getPerimeter()->getScale();
            }

            if ($searchSmaller) {
                // Si c'est une aide locale, on retire l'aide générique si présente dans la liste
                if ($aid->getGenericAid() && $aids->contains($aid->getGenericAid())) {
                    $aids->removeElement($aid->getGenericAid());
                }
            } elseif ($searchWider) {
                // Si c'est une aide locale et que la liste contiens l'aide générique,
                // on retire l'aide locale de la liste
                if ($aid->getGenericAid() && $aids->contains($aid->getGenericAid())) {
                    $aids->removeElement($aid);
                }
            }
        }

        return $aids->toArray();
    }

    public function getUrl(Aid $aid, int $interface = UrlGeneratorInterface::ABSOLUTE_URL): ?string
    {
        try {
            return $this->routerInterface->generate('app_aid_aid_details', ['slug' => $aid->getSlug()], $interface);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function userCanExportPdf(Aid $aid, ?User $user): bool
    {
        $access = false;

        if (
            $user && $user->getId() == $aid->getAuthor()->getId()
            || $this->userService->isUserGranted($user, User::ROLE_ADMIN)
        ) {
            $access = true;
        }

        // Si l'utilisateur est dans l'organisation de l'aide et qu'il n'a pas demandé une edition privée
        if ($user && $aid->getOrganization() && $aid->getOrganization()->getBeneficiairies()->contains($user)) {
            $access = true;
        }

        return $access;
    }

    public function userCanSee(Aid $aid, ?User $user): bool
    {
        $return = true;

        if (!$aid->isPublished()) {
            if ($user && $aid->getAuthor() && ($user->getId() == $aid->getAuthor()->getId())) { // c'est l'auteur
                $return = true;
            } elseif (
                $user
                && $aid->getOrganization()
                && $aid->getOrganization()->getBeneficiairies()->contains($user)
            ) { // le user fait parti de l'organization de l'aide
                $return = true;
            } elseif ($user && $this->userService->isUserGranted($user, User::ROLE_ADMIN)) { // c'est un admin
                $return = true;
            } else {
                $return = false;
            }
        } else {
            $return = true;
        }

        return $return;
    }

    public function userCanEdit(Aid $aid, ?User $user): bool
    {
        $access = false;

        if (!$user instanceof User) {
            return false;
        }

        // si c'est l'auteur ou un admin
        if ($aid->getAuthor() == $user || $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
            $access = true;
        }

        // Si l'utilisateur est dans l'organisation de l'aide et qu'il n'a pas demandé une edition privée
        if (
            !$aid->isPrivateEdition()
            && $aid->getOrganization()
            && $aid->getOrganization()->getBeneficiairies()->contains($user)
        ) {
            $access = true;
        }

        return $access;
    }

    public function userCanDuplicate(Aid $aid, ?User $user): bool
    {
        return $this->userCanEdit($aid, $user);
    }

    /**
     * Recupère les données chez Démarche Simplifiée (DS).
     *
     * @return array<string, mixed>
     */
    public function getDatasFromDs(Aid $aid, ?User $user, ?Organization $organization): array
    {
        $datas = [
            'prepopulate_application_url' => false,
            'ds_folder_id' => false,
            'ds_folder_number' => false,
            'ds_application_url' => false,
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

        $organizationType = ($user->getDefaultOrganization() && $user->getDefaultOrganization()->getOrganizationType())
            ? $user->getDefaultOrganization()->getOrganizationType() : null;
        if (
            $organizationType
            && in_array($organizationType->getSlug(), [OrganizationType::SLUG_COMMUNE, OrganizationType::SLUG_EPCI])
        ) {
            try {
                $response = $this->postPrepopulateData($aid->getDsId(), $aid->getDsMapping(), $user, $organization);
                $content = json_decode((string) $response->getContent());

                $datas['prepopulate_application_url'] = $content->dossier_url ?? null;
                $datas['ds_folder_id'] = $content->dossier_id ?? null;
                $datas['ds_folder_number'] = $content->dossier_number ?? null;
            } catch (\Exception $e) {
                $this->loggerInterface->error('Erreur getDatasFromDs', [
                    'exception' => $e,
                    'idAid' => $aid->getId(),
                    'idUser' => $user->getId(),
                    'idOrganization' => $organization->getId(),
                ]);
            }
        }

        return $datas;
    }

    /**
     * Appel l'API Démarche Simplifiée (DS).
     *
     * @param array<string, mixed> $dsMapping
     */
    public function postPrepopulateData(
        int $dsId,
        array $dsMapping,
        ?UserInterface $user,
        ?Organization $organization,
    ): mixed {
        $datas = $this->prepopulateDsFolder($dsMapping, $user, $organization);

        return $this->httpClientInterface->request(
            'POST',
            'https://www.demarches-simplifiees.fr/api/public/v1/demarches/' . $dsId . '/dossiers',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $datas,
            ]
        );
    }

    /**
     * Fait le tableau de données à envoyer à Démarche Simplifiée (DS).
     *
     * @param array<string, mixed> $dsMapping
     *
     * @return array<string, mixed>
     */
    public function prepopulateDsFolder(array $dsMapping, ?UserInterface $user, ?Organization $organization): array
    {
        $datas = [];

        try {
            foreach ($dsMapping['FieldsList'] as $field) {
                if (isset($field['response_value']) && !empty($field['response_value'])) {
                    $datas[$field['ds_field_id']] = $field['response_value'];
                } elseif (
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

                        default:
                            break;
                    }
                    if (isset($value) && $value) {
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
     * basé sur les nom de champ Django.
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

                case 'first_name':
                    return $entity->getFirstname();

                case 'email':
                    return $entity->getEmail();

                default:
                    break;
            }
        } elseif ($entity instanceof Organization) {
            if ('organizationType' == $oldField) {
                return $entity->getOrganizationType() ? $entity->getOrganizationType()->getName() : null;
            }
        }

        return null;
    }

    public function canUserLock(Aid $aid, User $user): bool
    {
        if ($aid->getAuthor() == $user || $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
            return true;
        }

        if ($aid->getOrganization() && $aid->getOrganization()->getBeneficiairies()->contains($user)) {
            return true;
        }

        return false;
    }

    public function getLock(Aid $aid): ?AidLock
    {
        foreach ($aid->getAidLocks() as $aidLock) {
            return $aidLock;
        }

        return null;
    }

    public function isLockedByAnother(Aid $aid, User $user): bool
    {
        $now = new \DateTime(date('Y-m-d H:i:s'));
        $minutesMax = 5;
        foreach ($aid->getAidLocks() as $aidLock) {
            // si le lock a plus de 5 min, on le supprime
            if ($aidLock->getTimeStart() < $now->sub(new \DateInterval('PT' . $minutesMax . 'M'))) {
                $this->managerRegistry->getManager()->remove($aidLock);
                $this->managerRegistry->getManager()->flush();
                continue;
            }

            if ($aidLock->getUser() != $user) {
                return true;
            }
        }

        return false;
    }

    public function isLocked(Aid $aid): bool
    {
        return !$aid->getAidLocks()->isEmpty();
    }

    public function lock(Aid $aid, User $user): void
    {
        // vérifie que l'aide n'est pas déjà lock
        if ($aid->getAidLocks()->isEmpty()) {
            $aidLock = new AidLock();
            $aidLock->setAid($aid);
            $aidLock->setUser($user);
            $this->managerRegistry->getManager()->persist($aidLock);
            $this->managerRegistry->getManager()->flush();
        } else {
            $aidLock = (isset($aid->getAidLocks()[0]) && $aid->getAidLocks()[0] instanceof AidLock)
                ? $aid->getAidLocks()[0]
                : null;
            // on met à jour le lock si le user et l'aide sont bien les mêmes
            if ($aidLock && $aidLock->getUser() == $user && $aidLock->getAid() == $aid) {
                $aidLock->setTimeStart(new \DateTime(date('Y-m-d H:i:s')));
                $aidLock->setAid($aid);
                $aidLock->setUser($user);
                $this->managerRegistry->getManager()->persist($aidLock);
                $this->managerRegistry->getManager()->flush();
            }
        }
    }

    public function unlock(Aid $aid): void
    {
        foreach ($aid->getAidLocks() as $aidLock) {
            $this->managerRegistry->getManager()->remove($aidLock);
        }
        $this->managerRegistry->getManager()->flush();
    }

    /**
     * Extraits les mots clés.
     *
     * @return array<int, array<string, mixed>>
     */
    public function extractKeywords(Aid $aid): array
    {
        // concatene les textes bruts
        $text = $aid->getName() . ' '
            . strip_tags((string) $aid->getDescription()) . ' '
            . strip_tags((string) $aid->getEligibility()) . ' '
            . strip_tags((string) $aid->getContact());

        $commonWords = [
            'pour',
            'des',
            'ces',
            'que',
            'qui',
            'nous',
            'vous',
            'mais',
            'avec',
            'cette',
            'dans',
            'sur',
            'fait',
            'elle',
            'tout',
            'son',
            'sont',
            'aux',
            'par',
            'comme',
            'peut',
            'plus',
            'sans',
            'ses',
            'donc',
            'quand',
            'depuis',
            'leur',
            'sous',
            'tous',
            'très',
            'fait',
            'était',
            'aussi',
            'cela',
            'entre',
            'avant',
            'après',
            'tous',
            'autre',
            'trop',
            'encore',
            'alors',
            'ainsi',
            'chez',
            'leurs',
            'dont',
            'cette',
            'faire',
            'part',
            'quel',
            'elle',
            'même',
            'moins',
            'peu',
            'car',
            'aucun',
            'chaque',
            'toute',
            'fois',
            'quelque',
            'manière',
            'chose',
            'autres',
            'beaucoup',
            'toutes',
            'ceux',
            'celles',
            'devant',
            'depuis',
            'derrière',
            'dessous',
            'dessus',
            'contre',
            'pendant',
            'malgré',
            'hors',
            'parmi',
            'sans',
            'sauf',
            'selon',
            'sous',
            'vers',
        ];

        // Retirer les caractères spéciaux sauf les caractères accentués
        $text = preg_replace('/[^a-z0-9\sàâäéèêëïîôöùûüÿç]/ui', '', $text);

        // Retirer les mots de moins de 3 lettres
        $text = preg_replace('/\b\w{1,2}\b/u', '', $text);

        // Retirer les mots communs
        $commonWordsPattern = '/\b(' . implode('|', $commonWords) . ')\b/ui';
        $text = preg_replace($commonWordsPattern, '', $text);

        /** @var KeywordReferenceRepository $keywordReferenceRepository */
        $keywordReferenceRepository = $this->managerRegistry->getRepository(KeywordReference::class);

        // Tokenize la description
        $tokens = tokenize($text);

        // rempli un tableau avec les mots importants
        /** @var ArrayCollection<int, KeywordReference> */
        $keywords = new ArrayCollection();
        $keywordsReturn = [];
        $freqDist = freq_dist($tokens);
        foreach ($freqDist->getKeyValuesByFrequency() as $item => $freq) {
            if ($freq < 2) {
                continue;
            }

            $keyword = $keywordReferenceRepository->findOneBy([
                'name' => $item,
                'intention' => false,
            ]);
            if (
                $keyword instanceof KeywordReference
                && $keyword->getParent() instanceof KeywordReference
                && !$keywords->contains($keyword->getParent())
            ) {
                $keywords->add($keyword->getParent());
                $keywordsReturn[] = [
                    'keyword' => $keyword->getParent(),
                    'freq' => $freq,
                ];
            }
        }

        return $keywordsReturn;
    }

    /**
     * @param Aid[] $aids
     */
    private function getAidStatsSpreadSheet(
        array $aids,
        \DateTime $dateMin,
        \DateTime $dateMax,
        StringService $stringService,
        LogAidViewService $logAidViewService,
        LogAidApplicationUrlClickService $logAidApplicationUrlClickService,
        LogAidOriginUrlClickService $logAidOriginUrlClickService,
        AidProjectService $aidProjectService,
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $firstAid = true;

        foreach ($aids as $aid) {
            $sheet = $firstAid ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $firstAid = false;

            $sheetTitle = preg_replace('/[^a-zA-Z0-9_]/', '', $aid->getId() . '_' . $aid->getName());
            $sheetTitle = $stringService->truncate($sheetTitle, 31);
            $sheet->setTitle($sheetTitle);

            $sheet->setCellValue('A1', 'Nom de l\'aide');
            $sheet->setCellValue('B1', $aid->getName());
            $sheet->setCellValue('A2', 'Url de l\'aide');
            $sheet->setCellValue('B2', $aid->getUrl());
            $sheet->setCellValue('A3', '');

            $headers = [
                'Date',
                'Nombre de vues',
                'Nombre de clics sur Candidater',
                'Nombre de clics sur Plus d\'informations',
                'Nombre de projets privés liés',
                'Nombre de projets publics liés',
            ];
            $sheet->fromArray($headers, null, 'A4');

            $nbViewsByDay = $logAidViewService->getCountByDay($aid, $dateMin, $dateMax);
            $nbApplicationUrlClicksByDay = $logAidApplicationUrlClickService->getCountByDay($aid, $dateMin, $dateMax);
            $nbOriginUrlClicksByDay = $logAidOriginUrlClickService->getCountByDay($aid, $dateMin, $dateMax);
            $nbProjectPublicsByDay = $aidProjectService->getCountByDay($aid, $dateMin, $dateMax, true);
            $nbProjectPrivatesByDay = $aidProjectService->getCountByDay($aid, $dateMin, $dateMax, false);

            $currentDay = clone $dateMin;
            $rowIndex = 5;
            while ($currentDay <= $dateMax) {
                $dataRow = [
                    $currentDay->format('d/m/Y'),
                    $nbViewsByDay[$currentDay->format('Y-m-d')] ?? '0',
                    $nbApplicationUrlClicksByDay[$currentDay->format('Y-m-d')] ?? '0',
                    $nbOriginUrlClicksByDay[$currentDay->format('Y-m-d')] ?? '0',
                    $nbProjectPublicsByDay[$currentDay->format('Y-m-d')] ?? '0',
                    $nbProjectPrivatesByDay[$currentDay->format('Y-m-d')] ?? '0',
                ];
                $sheet->fromArray($dataRow, null, 'A' . $rowIndex);
                ++$rowIndex;
                $currentDay->add(new \DateInterval('P1D'));
            }

            $sheet->setAutoFilter('A4:F4');
        }

        return $spreadsheet;
    }

    /**
     * Génère un fichier Excel contenant les statistiques des aides.
     */
    public function getAidStatsSpreadSheetOfUser(
        User $user,
        \DateTime $dateMin,
        \DateTime $dateMax,
        AidRepository $aidRepository,
        StringService $stringService,
        LogAidViewService $logAidViewService,
        LogAidApplicationUrlClickService $logAidApplicationUrlClickService,
        LogAidOriginUrlClickService $logAidOriginUrlClickService,
        AidProjectService $aidProjectService,
    ): Spreadsheet {
        $aidsParams = [
            'userWithOrganizations' => $user,
            'orderBy' => [
                'sort' => 'a.dateCreate',
                'order' => 'DESC',
            ],
        ];
        $aids = $aidRepository->findCustom($aidsParams);

        return $this->getAidStatsSpreadSheet(
            $aids,
            $dateMin,
            $dateMax,
            $stringService,
            $logAidViewService,
            $logAidApplicationUrlClickService,
            $logAidOriginUrlClickService,
            $aidProjectService
        );
    }

    public function getAidStatsSpreadSheetOfBacker(
        Backer $backer,
        \DateTime $dateMin,
        \DateTime $dateMax,
        AidRepository $aidRepository,
        StringService $stringService,
        LogAidViewService $logAidViewService,
        LogAidApplicationUrlClickService $logAidApplicationUrlClickService,
        LogAidOriginUrlClickService $logAidOriginUrlClickService,
        AidProjectService $aidProjectService,
    ): Spreadsheet {
        $aidsParams = [
            'backer' => $backer,
            'orderBy' => [
                'sort' => 'a.dateCreate',
                'order' => 'DESC',
            ],
        ];
        $aids = $aidRepository->findCustom($aidsParams);

        return $this->getAidStatsSpreadSheet(
            $aids,
            $dateMin,
            $dateMax,
            $stringService,
            $logAidViewService,
            $logAidApplicationUrlClickService,
            $logAidOriginUrlClickService,
            $aidProjectService
        );
    }

    /**
     * Fonction de recherche des aides.
     * On recupère uniquement les ids et le score total qui seront mis en cache.
     *
     * @param array<string, mixed> $aidParams
     *
     * @return array<int, Aid>
     */
    public function searchAidsV3(array $aidParams): array
    {
        // la clé du cache selon la recherche
        $cacheKey = 'search_aids_' . hash('xxh128', serialize([
            'params' => $aidParams,
            'date' => (new \DateTime())->format('Y-m-d'),
        ]));

        // on recupère les aides dans le cache si possible, sinon on calcul
        $aids = $this->cache->get($cacheKey, function (ItemInterface $item) use ($aidParams) {
            /** @var AidRepository $aidRepository */
            $aidRepository = $this->managerRegistry->getRepository(Aid::class);
            $aids = $aidRepository->findForSearchV3($aidParams);

            // déduplication des aides génériques / locales
            if (!isset($aidParams['noPostPopulate'])) {
                $aids = $this->postPopulateAids($aids, $aidParams);
            }

            $item->tag('search_aids');

            return $aids;
        });

        return $aids;
    }

    /**
     * Recupère les données des aides à partir des ids et du score total.
     *
     * @param array<int, mixed> $lightAids
     * @param array<string, mixed> $aidParams
     * @return array<int, Aid>
     */
    public function hydrateLightAids(array $lightAids, array $aidParams): array
    {
        if (empty($lightAids)) {
            return [];
        }
        // faits les tableaux d'ids et de scores
        $ids = array_map(fn ($aid) => $aid->getId(), $lightAids);
        $scoreTotalById = array_combine(
            array_map(fn ($aid) => $aid->getId(), $lightAids),
            array_map(fn ($aid) => $aid->getScoreTotal(), $lightAids)
        );

        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        // récupère les aides
        $aids = $aidRepository->findCompleteAidsByIds($ids);

        foreach ($aids as $key => $aid) {
            // on remet les scores
            $aids[$key]->setScoreTotal($scoreTotalById[$aid->getId()]);

            // on met en highlight les projets référents recherchés
            if (isset($aidParams['projectReference']) && $aidParams['projectReference'] instanceof ProjectReference) {
                foreach ($aid->getProjectReferences() as $projectReference) {
                    if ($projectReference->getId() == $aidParams['projectReference']->getId()) {
                        $aid->addProjectReferenceSearched($aidParams['projectReference']);
                    }
                }
            }
        }

        return $aids;
    }

        /**
     * Recupère les données des aides à partir des ids et du score total.
     *
     * @param array<int, mixed> $lightAids
     * @param array<string, mixed> $aidParams
     * @return array<int, Aid>
     */
    public function hydrateLightAidsFromVapp(array $lightAids, array $aidParams): array
    {
        if (empty($lightAids)) {
            return [];
        }
        // faits les tableaux d'ids et de scores
        $ids = array_map(fn ($aid) => $aid['id'], $lightAids);
        // $scoreTotalById = array_combine(
        //     array_map(fn ($aid) => $aid['id'], $lightAids),
        //     array_map(fn ($aid) => $aid['score_total'], $lightAids)
        // );

        $scoreTotalById = $this->requestStack->getCurrentRequest()->getSession()->get(VappApiService::SESSION_AIDS_SCORES, []);

        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        // récupère les aides
        $aids = $aidRepository->findCompleteAidsByIds($ids);

        foreach ($aids as $key => $aid) {
            // on remet les scores
            $aids[$key]->setScoreTotal($scoreTotalById[$aid->getId()]['score_total']);

            // on met le score vapp
            $aids[$key]->setScoreVapp($scoreTotalById[$aid->getId()]['score_vapp']);
            // on met en highlight les projets référents recherchés
            if (isset($aidParams['projectReference']) && $aidParams['projectReference'] instanceof ProjectReference) {
                foreach ($aid->getProjectReferences() as $projectReference) {
                    if ($projectReference->getId() == $aidParams['projectReference']->getId()) {
                        $aid->addProjectReferenceSearched($aidParams['projectReference']);
                    }
                }
            }
        }

        return $aids;
    }

    /**
     * Recupère les données des aides pour Vapp à partir des ids et du score total.
     *
     * @param array<int, mixed> $lightAids
     * @param array<string, mixed> $aidParams
     * @return array<int, Aid>
     */
    public function hydrateLightAidsForVapp(array $lightAids): array
    {
        if (empty($lightAids)) {
            return [];
        }
        // faits les tableaux d'ids et de scores
        $ids = array_map(fn ($aid) => $aid['id'], $lightAids);
        $scoreTotalById = array_combine(
            array_map(fn ($aid) => $aid['id'], $lightAids),
            array_map(fn ($aid) => $aid['score_total'], $lightAids)
        );

        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        // récupère les aides
        $aids = $aidRepository->findVappAidsByIds($ids);
        $converter = new HtmlConverter();
        foreach ($aids as $key => $aid) {
            // on remet les scores
            $aids[$key]['scoreTotal'] = $scoreTotalById[$aid['id']];
            $aids[$key]['description'] = $converter->convert($aid['description']);
        }

        return $aids;
    }

    public function isAidInUserFavorites(?User $user, ?Aid $aid): bool
    {
        try {
            if (!$user instanceof User || !$aid instanceof Aid) {
                return false;
            }

            $favoriteAids = $user->getFavoriteAids();
            if (!$favoriteAids instanceof Collection || $favoriteAids->isEmpty()) {
                return false;
            }

            foreach ($favoriteAids as $favoriteAid) {
                if (!$favoriteAid->getAid()) {
                    continue;
                }
                if ($favoriteAid->getAid()->getId() === $aid->getId()) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
