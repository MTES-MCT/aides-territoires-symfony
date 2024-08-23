<?php

namespace App\Service\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidInstructor;
use App\Entity\Aid\AidLock;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Repository\Aid\AidRepository;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AidService // NOSONAR too complex
{
    public function __construct(
        private HttpClientInterface $httpClientInterface,
        private UserService $userService,
        private RouterInterface $routerInterface,
        private ReferenceService $referenceService,
        private ManagerRegistry $managerRegistry,
        private LoggerInterface $loggerInterface
    )
    {
        
    }

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
            $aids = $this->searchAids(
                [
                    'id' => $aid->getId(),
                    'keyword' => $projectReference->getName()
                ]
            );
            if (!empty($aids)) {
                $projectReferencesSuggestions[] = $projectReference;
            }
        }
        return $projectReferencesSuggestions;
    }

    public function getAidDuplicates(Aid $aid): array
    {
        if (!$aid->getOriginUrl()) {
            return [];
        }

        $aidRepository = $this->managerRegistry->getRepository(Aid::class);
        return $aidRepository->findCustom(
            [
                'originUrl' => $aid->getOriginUrl(),
                'exclude' => $aid,
                'perimeter' => $aid->getPerimeter() ?? null,
                'showInSearch' => true
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

    public function canUserAccessStatsPage(?User $user, Aid $aid) : bool
    {
        if (!$user instanceof User || !$aid instanceof Aid) {
            return false;
        }

        $access = false;
        if(
            $aid->getAuthor() == $user
            || $this->userService->isUserGranted($user, User::ROLE_ADMIN)
            || ($aid->getOrganization() && $aid->getOrganization()->getBeneficiairies()->contains($user))
        ) {
            $access = true;
        }

        return $access;
        
    }

    public function searchAids(array $aidParams): array
    {
        /** @var AidRepository $aidRepo */
        $aidRepo = $this->managerRegistry->getRepository(Aid::class);
        $aids = $aidRepo->findCustom($aidParams);

        if (isset($aidParams['projectReference']) && $aidParams['projectReference'] instanceof ProjectReference) {
            /** @var Aid $aid */
            foreach ($aids as $aid) {
                if ($aid->getProjectReferences()->contains($aidParams['projectReference'])) {
                    $aid->addProjectReferenceSearched($aidParams['projectReference']);
                }
            }
        }

        if (!isset($aidParams['noPostPopulate']) && !isset($aidParams['notPostPopulate'])) {
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
    public function handleSearchPageRules(array $aids, $params): array // NOSONAR too complex
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
    public function unDuplicateGenerics(array $aids, ?Perimeter $perimeter) : array // NOSONAR too complex
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
        foreach ($aids as $aid) {
            // Si on a un périmètre de recherche
            if ($perimeterSearch) {
                $searchSmaller = $perimeterScale <= $aid->getPerimeter()->getScale();
                $searchWider = $perimeterScale > $aid->getPerimeter()->getScale();
            }

            if ($searchSmaller) {
                // Si c'est une aide locale, on retire l'aide générique si présente dans la liste
                if ($aid->getGenericAid() && $aids->contains($aid->getGenericAid())) {
                    $aids->removeElement($aid->getGenericAid());
                }
            } elseif ($searchWider) {
                // Si c'est une aide locale et que la liste contiens l'aide générique, on retire l'aide locale de la liste
                if ($aid->getGenericAid() && $aids->contains($aid->getGenericAid())) {
                    $aids->removeElement($aid);
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
        return false;
    }

    public function userCanSee(Aid $aid, ?User $user) : bool {
        if (!$aid->isPublished()) {
            if ($user && $aid->getAuthor() && ($user->getId() == $aid->getAuthor()->getId())) { // c'est l'auteur
                return true;
            } elseif ($user && $aid->getOrganization() && $aid->getOrganization()->getBeneficiairies() && $aid->getOrganization()->getBeneficiairies()->contains($user)) { // le user fait parti de l'organization de l'aide
                return true;
            } elseif ($user && $this->userService->isUserGranted($user, User::ROLE_ADMIN)) { // c'est un admin
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

        return false;
    }

    public function userCanDuplicate(Aid $aid, ?User $user) : bool
    {
        return $this->userCanEdit($aid, $user);
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
        if ($organizationType && in_array($organizationType->getSlug(), [OrganizationType::SLUG_COMMUNE, OrganizationType::SLUG_EPCI])) {
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
                    'idOrganization' => $organization->getId()
                ]);
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
     */
    public function postPrepopulateData(int $dsId, array $dsMapping, ?UserInterface $user, ?Organization $organization): mixed
    {
        $datas = $this->prepopulateDsFolder($dsMapping, $user, $organization);

        return $this->httpClientInterface->request(
            'POST',
            'https://www.demarches-simplifiees.fr/api/public/v1/demarches/'.$dsId.'/dossiers',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $datas
            ]
        );
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

                default:
                break;
            }
        } elseif ($entity instanceof Organization) {
            if ($oldField == 'organizationType') {
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
            if ($aidLock->getTimeStart() < $now->sub(new \DateInterval('PT'.$minutesMax.'M'))) {
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

    public function extractKeywords(Aid $aid): array
    {
        // concatene les textes bruts
        $text = $aid->getName(). ' '
                . strip_tags($aid->getDescription()). ' '
                . strip_tags($aid->getEligibility()). ' '
                . strip_tags($aid->getContact())
                ;
        
        $commonWords = [
            'pour', 'des', 'ces', 'que', 'qui', 'nous', 'vous', 'mais', 'avec', 'cette', 'dans', 'sur', 'fait', 'elle', 'tout', 'son', 'sont', 'aux', 'par', 'comme', 'peut', 'plus', 'sans', 'ses', 'donc', 'quand', 'depuis', 'leur', 'sous', 'tous', 'très', 'fait', 'était', 'aussi', 'cela', 'entre', 'avant', 'après', 'tous', 'autre', 'trop', 'encore', 'alors', 'ainsi', 'chez', 'leurs', 'dont', 'cette', 'faire', 'part', 'quel', 'elle', 'même', 'moins', 'peu', 'car', 'aucun', 'chaque', 'toute', 'fois', 'quelque', 'manière', 'chose', 'autres', 'beaucoup', 'toutes', 'ceux', 'celles', 'devant', 'depuis', 'derrière', 'dessous', 'dessus', 'contre', 'pendant', 'malgré', 'hors', 'parmi', 'sans', 'sauf', 'selon', 'sous', 'vers'
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
        $keywords = new ArrayCollection();
        $keywordsReturn = [];
        $freqDist = freq_dist($tokens);
        foreach ($freqDist->getKeyValuesByFrequency() as $item => $freq) {
            if ($freq < 2) {
                continue;
            }

            $keyword = $keywordReferenceRepository->findOneBy([
                'name' => $item,
                'intention' => false
            ]);
            if ($keyword instanceof KeywordReference && $keyword->getParent() && !$keywords->contains($keyword->getParent())) {
                $keywords->add($keyword->getParent());
                $keywordsReturn[] = [
                    'keyword' => $keyword->getParent(),
                    'freq' => $freq
                ];
            }
        }

        return $keywordsReturn;
    }
}
