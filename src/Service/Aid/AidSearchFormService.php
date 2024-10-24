<?php

namespace App\Service\Aid;

use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerGroup;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Reference\ProjectReference;
use App\Repository\Aid\AidDestinationRepository;
use App\Repository\Aid\AidStepRepository;
use App\Repository\Aid\AidTypeRepository;
use App\Repository\Backer\BackerRepository;
use App\Repository\Category\CategoryRepository;
use App\Repository\Keyword\KeywordSynonymlistRepository;
use App\Repository\Organization\OrganizationTypeRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Program\ProgramRepository;
use App\Service\User\UserService;
use App\Service\Various\StringService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AidSearchFormService
{
    const CATEGORY_SEARCH_PARAM_NAME = 'categorysearch';
    const AID_TYPE_PARAM_NAME = 'aidTypes';
    const BACKERS_PARAM_NAME = 'backers';
    const PROGRAMS_PARAM_NAME = 'programs[]';
    const AID_STEPS_PARAM_NAME = 'aidSteps[]';
    const AID_DESTINATIONS_PARAM_NAME = 'aidDestinations[]';

    const QUERYSTRING_KEY_KEYWORD = 'keyword';
    const QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS = 'organization_type_slugs';
    const QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG = 'organization_type_slug';
    const QUERYSTRING_KEY_ORGANIZATION_TYPE_IDS = 'organization_type_ids';
    const QUERYSTRING_KEY_CATEGORY_SLUGS = 'category_slugs';
    const QUERYSTRING_KEY_CATEGORY_IDS = 'category_ids';
    const QUERYSTRING_KEY_APPLY_BEFORE = 'apply_before';
    const QUERYSTRING_KEY_PUBLISHED_AFTER = 'published_after';
    const QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG = 'aid_type_group_slug';
    const QUERYSTRING_KEY_AID_TYPE_GROUP_ID = 'aid_type_group_id';
    const QUERYSTRING_KEY_AID_TYPE_SLUGS = 'aid_type_slugs';
    const QUERYSTRING_KEY_AID_TYPE_IDS = 'aid_type_ids';
    const QUERYSTRING_KEY_AID_STEP_SLUGS = 'aid_step_slugs';
    const QUERYSTRING_KEY_AID_STEP_IDS = 'aid_step_ids';
    const QUERYSTRING_KEY_AID_DESTINATION_SLUGS = 'aid_destination_slugs';
    const QUERYSTRING_KEY_AID_DESTINATION_IDS = 'aid_destination_ids';
    const QUERYSTRING_KEY_AID_RECURRENCE_SLUG = 'aid_recurrence_slug';
    const QUERYSTRING_KEY_AID_RECURRENCE_ID = 'aid_recurrence_id';
    const QUERYSTRING_KEY_IS_CALL_FOR_PROJECT = 'call_for_projects_only';
    const QUERYSTRING_KEY_IS_CHARGED = 'is_charged';
    const QUERYSTRING_KEY_SEARCH_PERIMETER = 'perimeter_id';
    const QUERYSTRING_KEY_PROJECT_REFERENCE_ID = 'project_reference_id';
    const QUERYSTRING_KEY_EUROPEAN_AID_SLUG = 'european_aid_slug';
    const QUERYSTRING_KEY_BACKER_IDS = 'backer_ids';
    const QUERYSTRING_KEY_BACKER_GROUP_ID = 'backer_group_id';
    const QUERYSTRING_KEY_PROGRAM_IDS = 'programIds';
    const QUERYSTRING_KEY_ORDER_BY = 'orderBy';
    const QUERYSTRING_KEY_NEW_INTEGRATION = 'newIntegration';

    public function __construct(
        protected RequestStack $requestStack,
        protected PerimeterRepository $perimeterRepository,
        protected OrganizationTypeRepository $organizationTypeRepository,
        protected KeywordSynonymlistRepository $keywordSynonymlistRepository,
        protected CategoryRepository $categoryRepository,
        protected FormFactoryInterface $formFactory,
        protected ManagerRegistry $managerRegistry,
        protected StringService $stringService,
        protected UserService $userService
    ) {
    }

    public function countNbCriteriaFromAidSearchClass(AidSearchClass $aidSearchClass, $ignoreProperties = ['organizationType', 'searchPerimeter', 'orderBy']): int
    {
        $nbCriteria = 0;

        // Utilisez la réflexion pour obtenir toutes les propriétés de AidSearchClass
        $reflectionClass = new \ReflectionClass($aidSearchClass);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $propertyName = $property->getName();

            // Ignorer les propriétés spécifiées
            if (in_array($propertyName, $ignoreProperties)) {
                continue;
            }
            
            $getter = 'get' . ucfirst($propertyName);
            if (method_exists($aidSearchClass, $getter)) {
                $value = $aidSearchClass->$getter();
                if ($value instanceof ArrayCollection) {
                    if (!$value->isEmpty()) {
                        $nbCriteria++;
                    }
                } elseif ($value) {
                    $nbCriteria++;
                }
            }
        }

        return $nbCriteria;
    }

    public function convertAidSearchClassToQueryString(AidSearchClass $aidSearchClass): string
    { // NOSONAR too complex
        $params = [];

        if ($aidSearchClass->getOrganizationTypeSlug()) {
            $params[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG] = $aidSearchClass->getOrganizationTypeSlug()->getSlug();
        }
        if ($aidSearchClass->getPerimeterId()) {
            $params[self::QUERYSTRING_KEY_SEARCH_PERIMETER] = $aidSearchClass->getPerimeterId()->getId();
        }
        if ($aidSearchClass->getKeyword()) {
            $params[self::QUERYSTRING_KEY_KEYWORD] = $aidSearchClass->getKeyword();
        }
        if ($aidSearchClass->getCategoryIds()) {
            $categories = [];
            foreach ($aidSearchClass->getCategoryIds() as $category) {
                $categories[] = $category->getId();
            }
            $params[self::QUERYSTRING_KEY_CATEGORY_IDS] = $categories;
        }
        if ($aidSearchClass->isNewIntegration()) {
            $params[self::QUERYSTRING_KEY_NEW_INTEGRATION] = $aidSearchClass->isNewIntegration();
        }
        if ($aidSearchClass->getOrderBy()) {
            $params[self::QUERYSTRING_KEY_ORDER_BY] = $aidSearchClass->getOrderBy();
        }
        if ($aidSearchClass->getAidTypeIds()) {
            $aidTypes = [];
            foreach ($aidSearchClass->getAidTypeIds() as $aidType) {
                $aidTypes[] = $aidType->getId();
            }
            $params[self::QUERYSTRING_KEY_AID_TYPE_IDS] = $aidTypes;
        }
        if ($aidSearchClass->getBackerIds()) {
            $backers = [];
            foreach ($aidSearchClass->getBackerIds() as $backer) {
                $backers[] = $backer->getId();
            }
            $params[self::QUERYSTRING_KEY_BACKER_IDS] = $backers;
        }
        if ($aidSearchClass->getBackerGroupId()) {
            $params[self::QUERYSTRING_KEY_BACKER_GROUP_ID] = $aidSearchClass->getBackerGroupId()->getId();
        }
        if ($aidSearchClass->getApplyBefore()) {
            $params[self::QUERYSTRING_KEY_APPLY_BEFORE] = $aidSearchClass->getApplyBefore()->format('Y-m-d');
        }
        if ($aidSearchClass->getPrograms()) {
            $programs = [];
            foreach ($aidSearchClass->getPrograms() as $program) {
                $programs[] = $program->getId();
            }
            $params[self::QUERYSTRING_KEY_PROGRAM_IDS] = $programs;
        }
        if ($aidSearchClass->getAidStepIds()) {
            $aidSteps = [];
            foreach ($aidSearchClass->getAidStepIds() as $aidStep) {
                $aidSteps[] = $aidStep->getId();
            }
            $params[self::QUERYSTRING_KEY_AID_STEP_IDS] = $aidSteps;
        }
        if ($aidSearchClass->getAidDestinationIds()) {
            $aidDestinations = [];
            foreach ($aidSearchClass->getAidDestinationIds() as $aidDestination) {
                $aidDestinations[] = $aidDestination->getId();
            }
            $params[self::QUERYSTRING_KEY_AID_DESTINATION_IDS] = $aidDestinations;
        }
        if ($aidSearchClass->getIsCharged()) {
            $params[self::QUERYSTRING_KEY_IS_CHARGED] = $aidSearchClass->getIsCharged();
        }
        if ($aidSearchClass->getEuropeanAidSlug()) {
            $params[self::QUERYSTRING_KEY_EUROPEAN_AID_SLUG] = $aidSearchClass->getEuropeanAidSlug();
        }
        if ($aidSearchClass->getCallForProjectsOnly()) {
            $params[self::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT] = $aidSearchClass->getCallForProjectsOnly();
        }
        if ($aidSearchClass->getProjectReference()) {
            $params[self::QUERYSTRING_KEY_PROJECT_REFERENCE_ID] = $aidSearchClass->getProjectReference()->getId();
            if (!$aidSearchClass->getKeyword()) {
                $params[self::QUERYSTRING_KEY_KEYWORD] = $aidSearchClass->getProjectReference()->getName();
            }
        }

        return http_build_query($params);
    }

    public function convertAidSearchClassToAidParams(AidSearchClass $aidSearchClass): array // NOSONAR too complex
    {
        $aidParams = [];

        if ($aidSearchClass->getOrganizationTypeSlug()) {
            $aidParams['organizationType'] = $aidSearchClass->getOrganizationTypeSlug();
        }
        if ($aidSearchClass->getAudiences()) {
            $aidParams['organizationTypes'] = $aidSearchClass->getAudiences();
        }

        if ($aidSearchClass->getPerimeterId()) {
            $aidParams['perimeterFrom'] = $aidSearchClass->getPerimeterId();
        }

        if ($aidSearchClass->getKeyword()) {
            $aidParams['keyword'] = $aidSearchClass->getKeyword();
            // si pas de projet référent, on vérifie si on ne trouve pas avec le keyword
            if (!$aidSearchClass->getProjectReference()) {
                $projectReference = $this->managerRegistry->getRepository(ProjectReference::class)->findOneBy(['name' => $aidSearchClass->getKeyword()]);
                if ($projectReference instanceof ProjectReference) {
                    $aidParams['projectReference'] = $projectReference;
                }
            }
        }

        if ($aidSearchClass->getCategoryIds()) {
            $aidParams['categories'] = $aidSearchClass->getCategoryIds();
        }

        if ($aidSearchClass->getAidTypeIds()) {
            $aidParams['aidTypes'] = $aidSearchClass->getAidTypeIds();
        }

        if ($aidSearchClass->getOrderBy()) {
            $aidParams = array_merge($aidParams, $this->handleOrderBy(['orderBy' => $aidSearchClass->getOrderBy()]));
        }

        if ($aidSearchClass->getBackerIds()) {
            $aidParams['backers'] = $aidSearchClass->getBackerIds();
        }

        if ($aidSearchClass->getBackerGroupId()) {
            $aidParams['backerGroup'] = $aidSearchClass->getBackerGroupId();
        }

        if ($aidSearchClass->getApplyBefore()) {
            $aidParams['applyBefore'] = $aidSearchClass->getApplyBefore();
        }

        if ($aidSearchClass->getPublishedAfter()) {
            $aidParams['publishedAfter'] = $aidSearchClass->getPublishedAfter();
        }

        if ($aidSearchClass->getPrograms()) {
            $aidParams['programs'] = $aidSearchClass->getPrograms();
        }

        if ($aidSearchClass->getAidStepIds()) {
            $aidParams['aidSteps'] = $aidSearchClass->getAidStepIds();
        }

        if ($aidSearchClass->getAidDestinationIds()) {
            $aidParams['aidDestinations'] = $aidSearchClass->getAidDestinationIds();
        }

        if ($aidSearchClass->getIsCharged() !== null) {
            $aidParams['isCharged'] = $aidSearchClass->getIsCharged();
        }

        if ($aidSearchClass->getEuropeanAidSlug()) {
            $aidParams['europeanAid'] = $aidSearchClass->getEuropeanAidSlug();
        }

        if ($aidSearchClass->getCallForProjectsOnly()) {
            $aidParams['isCallForProject'] = $aidSearchClass->getCallForProjectsOnly();
        }

        if ($aidSearchClass->getProjectReference()) {
            $aidParams['projectReference'] = $aidSearchClass->getProjectReference();
        }

        if ($aidSearchClass->getAidRecurrence()) {
            $aidParams['aidRecurrence'] = $aidSearchClass->getAidRecurrence();
        }

        return $aidParams;
    }

    public function getAidSearchClass(?AidSearchClass $aidSearchClass = null, ?array $params = null): AidSearchClass // NOSONAR too complex
    {
        if (!$aidSearchClass) {
            $aidSearchClass = new AidSearchClass();
        }

        // < les paramètres en query
        $query = '';
        // si paramètre querystring (portails, alertes, ...)
        if (isset($params['querystring'])) {
            // on nettoie la chaine car certaines anciennes alertes ont des caractères HTML
            $query = strip_tags(str_replace(['&amp;'], ['&'], urldecode($params['querystring'])));
        }
        // Si on des paramètres dans l'url
        if ($this->requestStack->getCurrentRequest()) {
            if ($query !== '') {
                $query .= '&';
            }
            $query .= parse_url($this->requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;
        }

        // transforme en tableau
        $queryParams = $this->unduplicateSpecificKeys($this->normalizeQueryParams($this->parseQueryString($query)));
        // > les paramètres en query

        // < le user
        $user = $this->userService->getUserLogged();
        // > le user

        /**
         * < OrganizationTypes
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS])) {
            $organizationTypes = is_array($queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS]) ? $queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS] : [$queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS]];
            $organizationTypes = $this->stringService->forceElementsToString($organizationTypes);
            if (!empty($organizationTypes)) {
                /** @var OrganizationTypeRepository $organizationTypeRepository */
                $organizationTypeRepository = $this->managerRegistry->getRepository(OrganizationType::class);
                $organizationTypes = $organizationTypeRepository->findCustom([
                    'slugs' => $organizationTypes
                ]);
                foreach ($organizationTypes as $organizationType) {
                    $aidSearchClass->addAudience($organizationType);
                }
            }
        }
        if (isset($queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_IDS])) {
            $organizationTypes = is_array($queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_IDS]) ? $queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_IDS] : [$queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_IDS]];
            $organizationTypes = $this->stringService->forceElementsToInt($organizationTypes);
            if (!empty($organizationTypes)) {
                /** @var OrganizationTypeRepository $organizationTypeRepository */
                $organizationTypeRepository = $this->managerRegistry->getRepository(OrganizationType::class);
                $organizationTypes = $organizationTypeRepository->findCustom([
                    'ids' => $organizationTypes
                ]);
                foreach ($organizationTypes as $organizationType) {
                    $aidSearchClass->addAudience($organizationType);
                }
            }
        }
        /**
         * > OrganizationTypes
         */

        /**
         * < OrganizationType
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG])) {
            $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy(['slug' => (string) $queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG]]);
            if ($organizationType instanceof OrganizationType) {
                $aidSearchClass->setOrganizationTypeSlug($organizationType);
            }
        }

        // si pas de paramètre et pas de paramètres dans l'url on pends le type de l'organisation par défaut du user (on viens direcement sur l'url de recherche d'aide ou page d'accueil)
        if (
            !$aidSearchClass->getOrganizationTypeSlug()
            && empty($queryParams)
            && $user
            && $user->getDefaultOrganization()
            && $user->getDefaultOrganization()->getOrganizationType()
            && !isset($params['dontUseUserOrganizationType'])
        ) {
            $aidSearchClass->setOrganizationTypeSlug($user->getDefaultOrganization()->getOrganizationType());
        }

        if (
            is_array($params)
            && array_key_exists('forceOrganizationType', $params)
            && !$aidSearchClass->getOrganizationTypeSlug()
        ) {
            $aidSearchClass->setOrganizationTypeSlug($params['forceOrganizationType']);
        }

        if (isset($queryParams['forceOrganizationType']) && $queryParams['forceOrganizationType'] == 'null' && !$aidSearchClass->getOrganizationTypeSlug()) {
            $aidSearchClass->setOrganizationTypeSlug(null);
        }

        /**
         * > OrganizationType
         */


        /**
         * < Perimeter
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_SEARCH_PERIMETER])) {
            $perimeter = $this->managerRegistry->getRepository(Perimeter::class)->find((int) $queryParams[self::QUERYSTRING_KEY_SEARCH_PERIMETER]);
            if ($perimeter instanceof Perimeter) {
                $aidSearchClass->setPerimeterId($perimeter);
            }
        }

        // si pas de paramètre on pends le périmètre de l'organisation par défaut du user
        if (
            !$aidSearchClass->getPerimeterId()
            && !isset($params['dontUseUserPerimeter'])
            && empty($queryParams)
            && $user
            && $user->getDefaultOrganization()
            && $user->getDefaultOrganization()->getPerimeter()
        ) {
            $aidSearchClass->setPerimeterId($user->getDefaultOrganization()->getPerimeter());
        }

        /**
         * > Perimeter
         */

        /**
         * > Keyword
         */
        $keyword = null;
        // nouveau paramètre
        if (isset($queryParams[self::QUERYSTRING_KEY_KEYWORD])) {
            if (is_array($queryParams[self::QUERYSTRING_KEY_KEYWORD])) {
                $queryParams[self::QUERYSTRING_KEY_KEYWORD] = trim(implode(' ', $queryParams[self::QUERYSTRING_KEY_KEYWORD]));
            }
            $keyword = (string) $queryParams[self::QUERYSTRING_KEY_KEYWORD];
        }
        $aidSearchClass->setKeyword($keyword);
        /**
         * > Keyword
         */

        /**
         * > Categories
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_CATEGORY_SLUGS])) {
            $categorySlugs = is_array($queryParams[self::QUERYSTRING_KEY_CATEGORY_SLUGS]) ? $queryParams[self::QUERYSTRING_KEY_CATEGORY_SLUGS] : [$queryParams[self::QUERYSTRING_KEY_CATEGORY_SLUGS]];
            $categorySlugs = $this->stringService->forceElementsToString($categorySlugs);
            if (!empty($categorySlugs)) {
                /** @var CategoryRepository $categoryRepository */
                $categoryRepository = $this->managerRegistry->getRepository(Category::class);
                $categories = $categoryRepository->findCustom(
                    [
                        'slugs' => $categorySlugs
                    ]
                );
                foreach ($categories as $category) {
                    $aidSearchClass->addCategoryId($category);
                }
            }
        }

        if (isset($queryParams[self::QUERYSTRING_KEY_CATEGORY_IDS])) {
            $categoryIds = is_array($queryParams[self::QUERYSTRING_KEY_CATEGORY_IDS]) ? $queryParams[self::QUERYSTRING_KEY_CATEGORY_IDS] : [$queryParams[self::QUERYSTRING_KEY_CATEGORY_IDS]];
            $categoryIds = $this->stringService->forceElementsToInt($categoryIds);
            if (!empty($categoryIds)) {
                /** @var CategoryRepository $categoryRepository */
                $categoryRepository = $this->managerRegistry->getRepository(Category::class);
                
                $categories = $categoryRepository->findCustom(
                    [
                        'ids' => $categoryIds
                    ]
                );
                foreach ($categories as $category) {
                    $aidSearchClass->addCategoryId($category);
                }
            }
        }
        /**
        * > Categories
        */


        /**
         * > AidTypeGroup
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG])) {
            $aidTypeGroup = $this->managerRegistry->getRepository(AidTypeGroup::class)->findOneBy(['slug' => (string) $queryParams[self::QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG]]);
            if ($aidTypeGroup instanceof AidTypeGroup) {
                foreach ($aidTypeGroup->getAidTypes() as $aidType) {
                    $aidSearchClass->addAidTypeId($aidType);
                }
            }
        }

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_TYPE_GROUP_ID])) {
            $aidTypeGroup = $this->managerRegistry->getRepository(AidTypeGroup::class)->find((int) $queryParams[self::QUERYSTRING_KEY_AID_TYPE_GROUP_ID]);
            if ($aidTypeGroup instanceof AidTypeGroup) {
                foreach ($aidTypeGroup->getAidTypes() as $aidType) {
                    $aidSearchClass->addAidTypeId($aidType);
                }
            }
        }

        /**
         * < AidType
         */

        /**
         * > AidType
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_AID_TYPE_IDS])) {
            $aidTypeIds = is_array($queryParams[self::QUERYSTRING_KEY_AID_TYPE_IDS]) ? $queryParams[self::QUERYSTRING_KEY_AID_TYPE_IDS] : [$queryParams[self::QUERYSTRING_KEY_AID_TYPE_IDS]];
            $aidTypeIds = $this->stringService->forceElementsToInt($aidTypeIds);
            if (!empty($aidTypeIds)) {
                /** @var AidTypeRepository $aidTypeRepository */
                $aidTypeRepository = $this->managerRegistry->getRepository(AidType::class);
                $aidTypes = $aidTypeRepository->findCustom(
                    [
                        'ids' => $aidTypeIds
                    ]
                );
                foreach ($aidTypes as $aidType) {
                    $aidSearchClass->addAidTypeId($aidType);
                }
            }

        }

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_TYPE_SLUGS])) {
            $aidTypeSlugs = is_array($queryParams[self::QUERYSTRING_KEY_AID_TYPE_SLUGS]) ? $queryParams[self::QUERYSTRING_KEY_AID_TYPE_SLUGS] : [$queryParams[self::QUERYSTRING_KEY_AID_TYPE_SLUGS]];
            $aidTypeSlugs = $this->stringService->forceElementsToString($aidTypeSlugs);
            $aidTypeGroupSlugs = [AidTypeGroup::SLUG_FINANCIAL, AidTypeGroup::SLUG_TECHNICAL];
            
            // on regarde si il y a des slugs de groupes pour les extraires
            $groupSlugs = [];
            foreach ($aidTypeSlugs as $key => $slug) {
                if (in_array($slug, $aidTypeGroupSlugs)) {
                    $groupSlugs[] = $slug;
                    unset($aidTypeSlugs[$key]);
                }
            }

            if (!empty($groupSlugs)) {
                $aidTypeGroupRepository = $this->managerRegistry->getRepository(AidTypeGroup::class);
                foreach ($groupSlugs as $groupSlug) {
                    $aidTypeGroup = $aidTypeGroupRepository->findOneBy(['slug' => $groupSlug]);
                    if ($aidTypeGroup instanceof AidTypeGroup) {
                        foreach ($aidTypeGroup->getAidTypes() as $aidType) {
                            $aidSearchClass->addAidTypeId($aidType);
                        }
                    }
                }
            }

            if (!empty($aidTypeSlugs)) {
                /** @var AidTypeRepository $aidTypeRepository */
                $aidTypeRepository = $this->managerRegistry->getRepository(AidType::class);

                $aidTypes = $aidTypeRepository->findCustom([
                    'slugs' => $aidTypeSlugs
                ]);
                foreach ($aidTypes as $aidType) {
                    $aidSearchClass->addAidTypeId($aidType);
                }
            }
        }
        /**
         * > AidType
         */

        /**
         * < Backers
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_BACKER_IDS])) {
            $backersIds = is_array($queryParams[self::QUERYSTRING_KEY_BACKER_IDS]) ? $queryParams[self::QUERYSTRING_KEY_BACKER_IDS] : [$queryParams[self::QUERYSTRING_KEY_BACKER_IDS]];
            $backersIds = $this->stringService->forceElementsToInt($backersIds);

            if (!empty($backersIds)) {
                /** @var BackerRepository $backerRepository */
                $backerRepository = $this->managerRegistry->getRepository(Backer::class);
                $backers = $backerRepository->findCustom(
                    [
                        'ids' => $backersIds
                    ]
                );

                foreach ($backers as $backer) {
                    $aidSearchClass->addBackerId($backer);
                }
            }
        }
        /**
         * > Backers
         */

        /**
         * < BackerGroup
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_BACKER_GROUP_ID])) {
            $backerGroupRepository = $this->managerRegistry->getRepository(BackerGroup::class);;
            $backerGroup = $backerGroupRepository->find((int) $queryParams[self::QUERYSTRING_KEY_BACKER_GROUP_ID]);
            if ($backerGroup instanceof BackerGroup) {
                $aidSearchClass->setBackerGroupId($backerGroup);
            }
        }
        /**
         * < BackerGroup
         */

        /**
         * < Programs
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_PROGRAM_IDS])) {
            $programIds = is_array($queryParams[self::QUERYSTRING_KEY_PROGRAM_IDS]) ? $queryParams[self::QUERYSTRING_KEY_PROGRAM_IDS] : [$queryParams[self::QUERYSTRING_KEY_PROGRAM_IDS]];
            if (!empty($programIds)) {
                /** @var ProgramRepository $programRepository */
                $programRepository = $this->managerRegistry->getRepository(Program::class);
                $programs = $programRepository->findCustom(
                    [
                        'ids' => $programIds
                    ]
                );
                foreach ($programs as $program) {
                    $aidSearchClass->addProgram($program);
                }
            }


            // il peu y avoir des slugs dans ce paramètre
            $slugs = [];
            foreach ($programIds as $idProgram) {
                if (!is_numeric($idProgram)) {
                    $slugs[] = $idProgram;
                }
            }
            if (!empty($slugs)) {
                $programs = $programRepository->findCustom(
                    [
                        'slugs' => $slugs
                    ]
                );
                foreach ($programs as $program) {
                    $aidSearchClass->addProgram($program);
                }
            }
        }

        if (isset($params['forcePrograms'])) {
            if (!is_array($params['forcePrograms'])) {
                $params['forcePrograms'] = [$params['forcePrograms']];
            }
            foreach ($params['forcePrograms'] as $program) {
                if ($program instanceof Program) {
                    $aidSearchClass->addProgram($program);
                }
            }
        }

        /**
         * > Programs
         */

        /**
         * < AidStep
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_STEP_SLUGS])) {
            $aidStepSlugs = is_array($queryParams[self::QUERYSTRING_KEY_AID_STEP_SLUGS]) ? $queryParams[self::QUERYSTRING_KEY_AID_STEP_SLUGS] : [$queryParams[self::QUERYSTRING_KEY_AID_STEP_SLUGS]];
            $aidStepSlugs = $this->stringService->forceElementsToString($aidStepSlugs);

            if (!empty($aidStepSlugs)) {
                /** @var AidStepRepository $aidStepRepository */
                $aidStepRepository = $this->managerRegistry->getRepository(AidStep::class);
                $aidSteps = $aidStepRepository->findCustom(
                    [
                        'slugs' => $aidStepSlugs
                    ]
                );
                foreach ($aidSteps as $aidStep) {
                    $aidSearchClass->addAidStepId($aidStep);
                }
            }
        }

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_STEP_IDS])) {
            $aidStepIds = is_array($queryParams[self::QUERYSTRING_KEY_AID_STEP_IDS]) ? $queryParams[self::QUERYSTRING_KEY_AID_STEP_IDS] : [$queryParams[self::QUERYSTRING_KEY_AID_STEP_IDS]];
            $aidStepIds = $this->stringService->forceElementsToInt($aidStepIds);
            if (!empty($aidStepIds)) {
                /** @var AidStepRepository $aidStepRepository */
                $aidStepRepository = $this->managerRegistry->getRepository(AidStep::class);
                $aidSteps = $aidStepRepository->findCustom(
                    [
                        'ids' => $aidStepIds
                    ]
                );
                foreach ($aidSteps as $aidStep) {
                    $aidSearchClass->addAidStepId($aidStep);
                }
            }
        }
        /**
         * > AidStep
         */

        /**
         * < AidRecurrence
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_RECURRENCE_SLUG])) {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => (string) $queryParams[self::QUERYSTRING_KEY_AID_RECURRENCE_SLUG]]);
            if ($aidRecurrence instanceof AidRecurrence) {
                $aidSearchClass->setAidRecurrence($aidRecurrence);
            }
        }
        if (isset($queryParams[self::QUERYSTRING_KEY_AID_RECURRENCE_ID])) {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->find((int) $queryParams[self::QUERYSTRING_KEY_AID_RECURRENCE_ID]);
            if ($aidRecurrence instanceof AidRecurrence) {
                $aidSearchClass->setAidRecurrence($aidRecurrence);
            }
        }

        /**
         * > AidRecurrence
         */


        /**
         * < AidDestination
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_SLUGS])) {
            $aidDestinationSlugs = is_array($queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_SLUGS]) ? $queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_SLUGS] : [$queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_SLUGS]];
            $aidDestinationSlugs = $this->stringService->forceElementsToString($aidDestinationSlugs);
            if (!empty($aidDestinationSlugs)) {
                /** @var AidDestinationRepository $aidDestinationRepository */
                $aidDestinationRepository = $this->managerRegistry->getRepository(AidDestination::class);
                $aidDestinations = $aidDestinationRepository->findCustom(
                    [
                        'slugs' => $aidDestinationSlugs
                    ]
                );
                foreach ($aidDestinations as $aidDestination) {
                    $aidSearchClass->addAidDestinationId($aidDestination);
                }
            }
        }

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_IDS])) {
            $aidDestinationIds = is_array($queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_IDS]) ? $queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_IDS] : [$queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_IDS]];
            $aidDestinationIds = $this->stringService->forceElementsToInt($aidDestinationIds);
            if (!empty($aidDestinationIds)) {
                /** @var AidDestinationRepository $aidDestinationRepository */
                $aidDestinationRepository = $this->managerRegistry->getRepository(AidDestination::class);
                $aidDestinations = $aidDestinationRepository->findCustom(
                    [
                        'ids' => $aidDestinationIds
                    ]
                );
                foreach ($aidDestinations as $aidDestination) {
                    $aidSearchClass->addAidDestinationId($aidDestination);
                }
            }
        }
        /**
         * > AidDestination
         */


        /**
         * < isCharged
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_IS_CHARGED])) {
            $isCharged = $queryParams[self::QUERYSTRING_KEY_IS_CHARGED];
            if (trim($isCharged) !== '') {
                if (
                    trim(strtolower((string) $isCharged)) === 'false'
                    || trim(strtolower((string) $isCharged)) === 'off'
                    || (int) $isCharged == 0
                    ) {
                    $aidSearchClass->setIsCharged(false);
                } elseif (
                    trim(strtolower((string) $isCharged)) === 'true'
                    || trim(strtolower((string) $isCharged)) === 'on'
                    || (int) $isCharged == 1
                    ) {
                    $aidSearchClass->setIsCharged(true);
                }
            }
        }

        /**
         * > isCharged
         */

        /**
         * < europeanAid
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_EUROPEAN_AID_SLUG])) {
            $aidSearchClass->setEuropeanAidSlug((string) $queryParams[self::QUERYSTRING_KEY_EUROPEAN_AID_SLUG]);
        }

        /**
         * > europeanAid
         */

        /**
         * < isCallForProject
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT])) {
            $isCallForProject = trim(strtolower((string) $queryParams[self::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT]));
        
            if (
                $isCallForProject === 'false'
                || $isCallForProject === 'off'
                || $isCallForProject === '0'
                ) {
                $aidSearchClass->setCallForProjectsOnly(false);
            } elseif (
                $isCallForProject === 'true'
                || $isCallForProject === 'on'
                || $isCallForProject === '1'
                ) {
                $aidSearchClass->setCallForProjectsOnly(true);
            }
        }
        /**
         * > isCallForProject
         */



        /**
         * < ProjectReference
         */
        // si il y a un mot clé on va regarder si c'est le nom exact d'un projet référent
        if ($aidSearchClass->getKeyword()) {
            $projectReference = $this->managerRegistry->getRepository(ProjectReference::class)->findOneBy(['name' => $aidSearchClass->getKeyword()]);
            if ($projectReference instanceof ProjectReference) {
                $aidSearchClass->setProjectReference($projectReference);
            }
        }

        // depuis api
        if (isset($queryParams[self::QUERYSTRING_KEY_PROJECT_REFERENCE_ID])) {
            $projectReference = $this->managerRegistry->getRepository(ProjectReference::class)->find((int) $queryParams[self::QUERYSTRING_KEY_PROJECT_REFERENCE_ID]);
            if ($projectReference instanceof ProjectReference) {
                $aidSearchClass->setProjectReference($projectReference);
            }
        }
        /**
         * > ProjectReference
         */


        /**
         * < ApplyBefore
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_APPLY_BEFORE])) {
            $applyBefore = \DateTime::createFromFormat('Y-m-d', $queryParams[self::QUERYSTRING_KEY_APPLY_BEFORE]);
            if ($applyBefore instanceof \DateTime) {
                $aidSearchClass->setApplyBefore($applyBefore);
            }
        }
        /**
         * > ApplyBefore
         */


        /**
         * < PublishedAfter
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_PUBLISHED_AFTER])) {
            $publishedAfter = \DateTime::createFromFormat('Y-m-d', $queryParams[self::QUERYSTRING_KEY_PUBLISHED_AFTER]);
            if ($publishedAfter instanceof \DateTime) {
                $aidSearchClass->setPublishedAfter($publishedAfter);
            }
        }
        /**
         * > PublishedAfter
         */


        return $aidSearchClass;
    }
    /**
     * Gestion du tri des aides selon le choix du formulaire
     *
     * @param [type] $aidParams
     * @return array
     */
    public function handleOrderBy($aidParams): array
    {
        if (isset($aidParams['orderBy'])) {
            switch ($aidParams['orderBy']) {
                case 'submission_deadline':
                    $aidParams['orderByDateSubmissionDeadline'] = true;
                    unset($aidParams['orderBy']);
                    break;
                case 'publication_date':
                    $aidParams['orderBy'] = ['sort' => 'a.timePublished', 'order' => 'DESC'];
                    break;

                case 'relevance':
                    $aidParams['orderBy'] = ['sort' => 'score_total', 'order' => 'DESC'];
                    break;
                default:
                    break;
            }
        }

        return $aidParams;
    }

    /**
     * Détermine si le formulaire doit être affiché en entier ou non, selon si un des champs de la liste à été rempli.
     *
     * @param Form $formAidSearch
     * @return boolean
     */
    public function setShowExtended(AidSearchClass $aidSearchClass): bool
    {
        return $aidSearchClass->getAidTypeIds() ||
            $aidSearchClass->getBackerIds() ||
            $aidSearchClass->getApplyBefore() ||
            $aidSearchClass->getPrograms() ||
            $aidSearchClass->getAidStepIds() ||
            $aidSearchClass->getAidDestinationIds() ||
            $aidSearchClass->getIsCharged() !== null ||
            $aidSearchClass->getEuropeanAidSlug() ||
            $aidSearchClass->getCallForProjectsOnly() ||
            $aidSearchClass->getBackerGroupId();
    }

    // Transforme la querystring en array, en prenant en compte les doublons
    private function parseQueryString($query): array
    {
        $queryParams = [];
        $queryItems = explode('&', (string) $query);

        if (is_array($queryItems)) {
            foreach ($queryItems as $queyItem) {
                $param = explode('=', urldecode($queyItem));
                if (isset($param[0]) && isset($param[1])) {
                    $param[0] = strip_tags($param[0]);
                    // Supprime les crochets [] ou [0], [1], etc. des clés
                    $param[0] = preg_replace('/\[\d*\]$/', '', $param[0]);
                    $param[1] = strip_tags($param[1]);
                    if (isset($queryParams[$param[0]]) && is_array($queryParams[$param[0]])) {
                        $queryParams[$param[0]][] = $param[1];
                    } elseif (isset($queryParams[$param[0]]) && !is_array($queryParams[$param[0]])) {
                        $queryParams[$param[0]] = [$queryParams[$param[0]]];
                        $queryParams[$param[0]][] = $param[1];
                    } else {
                        $queryParams[$param[0]] = $param[1];
                    }
                }
            }
        }

        return $queryParams;
    }

    // transforme les clés anciens format en clés actuels
    private function normalizeQueryParams(array $queryParams): array
    {
        $transitionKeys = [
            'text' => self::QUERYSTRING_KEY_KEYWORD,
            'targeted_audiences' => self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS,
            'organizationType' => self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG,
            'categories' => self::QUERYSTRING_KEY_CATEGORY_SLUGS,
            'themes' => self::QUERYSTRING_KEY_CATEGORY_SLUGS,
            'categorysearch' => self::QUERYSTRING_KEY_CATEGORY_IDS,
            'categorySearch' => self::QUERYSTRING_KEY_CATEGORY_IDS,
            'applyBefore' => self::QUERYSTRING_KEY_APPLY_BEFORE,
            'publishedAfter' => self::QUERYSTRING_KEY_PUBLISHED_AFTER,
            'aidTypeGroup' => self::QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG,
            'aid_type' => self::QUERYSTRING_KEY_AID_TYPE_SLUGS,
            'aidType' => self::QUERYSTRING_KEY_AID_TYPE_SLUGS,
            'financial_aids' => self::QUERYSTRING_KEY_AID_TYPE_SLUGS,
            'technical_aids' => self::QUERYSTRING_KEY_AID_TYPE_SLUGS,
            'aidtypes' => self::QUERYSTRING_KEY_AID_TYPE_IDS,
            'aidTypes' => self::QUERYSTRING_KEY_AID_TYPE_IDS,
            'mobilization_step' => self::QUERYSTRING_KEY_AID_STEP_SLUGS,
            'aidSteps' => self::QUERYSTRING_KEY_AID_STEP_IDS,
            'destinations' => self::QUERYSTRING_KEY_AID_DESTINATION_SLUGS,
            'aidDestinations' => self::QUERYSTRING_KEY_AID_DESTINATION_IDS,
            'recurrence' => self::QUERYSTRING_KEY_AID_RECURRENCE_SLUG,
            'isCallForProject' => self::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT,
            'isCharged' => self::QUERYSTRING_KEY_IS_CHARGED,
            'searchPerimeter' => self::QUERYSTRING_KEY_SEARCH_PERIMETER,
            'perimeter' => self::QUERYSTRING_KEY_SEARCH_PERIMETER,
            'projectReferenceId' => self::QUERYSTRING_KEY_PROJECT_REFERENCE_ID,
            'european_aid' => self::QUERYSTRING_KEY_EUROPEAN_AID_SLUG,
            'europeanAid' => self::QUERYSTRING_KEY_EUROPEAN_AID_SLUG,
            'backers' => self::QUERYSTRING_KEY_BACKER_IDS,
            'backerschoice' => self::QUERYSTRING_KEY_BACKER_IDS,
            'backerGroup' => self::QUERYSTRING_KEY_BACKER_GROUP_ID,
            'programs' => self::QUERYSTRING_KEY_PROGRAM_IDS,
            'order_by' => self::QUERYSTRING_KEY_ORDER_BY,
            'new_integration' => self::QUERYSTRING_KEY_NEW_INTEGRATION

        ];

        $normalizedParams = [];
        foreach ($queryParams as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $keyValue => $itemValue) {
                    if (trim($itemValue) === '') {
                        unset($value[$keyValue]);
                    }
                }
                if (empty($value)) {
                    continue;
                }
            } else {
                if (trim($value) === '') {
                    continue;
                }
            }
            // Supprime les crochets [] ou [0], [1], etc. des clés
            $normalizedKey = preg_replace('/\[\d*\]$/', '', $key);
            // Vérifie si la clé normalisée existe dans le tableau de transition
            $normalizedKey = $transitionKeys[$normalizedKey] ?? $normalizedKey;
        
            // Si la clé normalisée existe déjà, ajoute la valeur au tableau existant
            if (isset($normalizedParams[$normalizedKey])) {
                if (is_array($normalizedParams[$normalizedKey])) {
                    $normalizedParams[$normalizedKey][] = str_replace(['_'], ['-'], $value);
                } else {
                    $normalizedParams[$normalizedKey] = [$normalizedParams[$normalizedKey], str_replace(['_'], ['-'], $value)];
                }
            } else {
                $normalizedParams[$normalizedKey] = str_replace(['_'], ['-'], $value);
            }
        }

        return $normalizedParams;
    }

    /**
     * Pour être de ne pas avoir de tableau sur certaines clés
     * On prends la dernière valeur si c'est un tableau
     *
     * @param array $queryParams
     * @return array
     */
    public function unduplicateSpecificKeys(array $queryParams): array
    {
        $mustBeNotArray = [
            self::QUERYSTRING_KEY_KEYWORD,
            self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG,
            self::QUERYSTRING_KEY_APPLY_BEFORE,
            self::QUERYSTRING_KEY_PUBLISHED_AFTER,
            self::QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG,
            self::QUERYSTRING_KEY_AID_TYPE_GROUP_ID,
            self::QUERYSTRING_KEY_AID_RECURRENCE_SLUG,
            self::QUERYSTRING_KEY_AID_RECURRENCE_ID,
            self::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT,
            self::QUERYSTRING_KEY_IS_CHARGED,
            self::QUERYSTRING_KEY_SEARCH_PERIMETER,
            self::QUERYSTRING_KEY_PROJECT_REFERENCE_ID,
            self::QUERYSTRING_KEY_EUROPEAN_AID_SLUG,
            self::QUERYSTRING_KEY_BACKER_GROUP_ID,
            self::QUERYSTRING_KEY_ORDER_BY,
            self::QUERYSTRING_KEY_NEW_INTEGRATION
        ];
        
        foreach ($queryParams as $key => $value) {
            if (in_array($key, $mustBeNotArray) && is_array($value)) {
                // Réindexer le tableau pour éviter les clés non définies
                $value = array_values($value);

                // Vérifier si le tableau n'est pas vide avant de prendre la dernière valeur
                if (!empty($value)) {
                    $queryParams[$key] = $value[count($value) - 1];
                } else {
                    // Si le tableau est vide, supprimer la clé
                    unset($queryParams[$key]);
                }
            }
        }

        return $queryParams;
    }
}
