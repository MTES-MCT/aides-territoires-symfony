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
    const QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS = 'organizationTypeSlugs';
    const QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG = 'organizationTypeSlug';
    const QUERYSTRING_KEY_CATEGORY_SLUGS = 'categorySlugs';
    const QUERYSTRING_KEY_CATEGORY_IDS = 'categoryIds';
    const QUERYSTRING_KEY_APPLY_BEFORE = 'applyBefore';
    const QUERYSTRING_KEY_PUBLISHED_AFTER = 'publishedAfter';
    const QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG = 'aidTypeGroupSlug';
    const QUERYSTRING_KEY_AID_TYPE_SLUGS = 'aidTypeSlugs';
    const QUERYSTRING_KEY_AID_TYPE_IDS = 'aidTypeIds';
    const QUERYSTRING_KEY_AID_STEP_SLUGS = 'aidStepSlugs';
    const QUERYSTRING_KEY_AID_STEP_IDS = 'aidStepIds';
    const QUERYSTRING_KEY_AID_DESTINATION_SLUGS = 'aidDestinationSlugs';
    const QUERYSTRING_KEY_AID_DESTINATION_IDS = 'aidDestinationIds';
    const QUERYSTRING_KEY_AID_RECURRENCE_SLUG = 'aidRecurrenceSlug';
    const QUERYSTRING_KEY_IS_CALL_FOR_PROJECT = 'isCallForProject';
    const QUERYSTRING_KEY_IS_CHARGED = 'isCharged';
    const QUERYSTRING_KEY_SEARCH_PERIMETER = 'searchPerimeter';
    const QUERYSTRING_KEY_PROJECT_REFERENCE_ID = 'projectReferenceId';
    const QUERYSTRING_KEY_EUROPEAN_AID_SLUG = 'europeanAidSlug';
    const QUERYSTRING_KEY_BACKER_IDS = 'backerIds';
    const QUERYSTRING_KEY_BACKER_GROUP_ID = 'backerGroupId';
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

        if ($aidSearchClass->getOrganizationType()) {
            $params[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG] = $aidSearchClass->getOrganizationType()->getSlug();
        }
        if ($aidSearchClass->getSearchPerimeter()) {
            $params[self::QUERYSTRING_KEY_SEARCH_PERIMETER] = $aidSearchClass->getSearchPerimeter()->getId();
        }
        if ($aidSearchClass->getKeyword()) {
            $params[self::QUERYSTRING_KEY_KEYWORD] = $aidSearchClass->getKeyword();
        }
        if ($aidSearchClass->getCategorySearch()) {
            $categories = [];
            foreach ($aidSearchClass->getCategorySearch() as $category) {
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
        if ($aidSearchClass->getAidTypes()) {
            $aidTypes = [];
            foreach ($aidSearchClass->getAidTypes() as $aidType) {
                $aidTypes[] = $aidType->getId();
            }
            $params[self::QUERYSTRING_KEY_AID_TYPE_IDS] = $aidTypes;
        }
        if ($aidSearchClass->getBackerschoice()) {
            $backers = [];
            foreach ($aidSearchClass->getBackerschoice() as $backer) {
                $backers[] = $backer->getId();
            }
            $params[self::QUERYSTRING_KEY_BACKER_IDS] = $backers;
        }
        if ($aidSearchClass->getBackerGroup()) {
            $params[self::QUERYSTRING_KEY_BACKER_GROUP_ID] = $aidSearchClass->getBackerGroup()->getId();
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
        if ($aidSearchClass->getAidSteps()) {
            $aidSteps = [];
            foreach ($aidSearchClass->getAidSteps() as $aidStep) {
                $aidSteps[] = $aidStep->getId();
            }
            $params[self::QUERYSTRING_KEY_AID_STEP_IDS] = $aidSteps;
        }
        if ($aidSearchClass->getAidDestinations()) {
            $aidDestinations = [];
            foreach ($aidSearchClass->getAidDestinations() as $aidDestination) {
                $aidDestinations[] = $aidDestination->getId();
            }
            $params[self::QUERYSTRING_KEY_AID_DESTINATION_IDS] = $aidDestinations;
        }
        if ($aidSearchClass->getIsCharged()) {
            $params[self::QUERYSTRING_KEY_IS_CHARGED] = $aidSearchClass->getIsCharged();
        }
        if ($aidSearchClass->getEuropeanAid()) {
            $params[self::QUERYSTRING_KEY_EUROPEAN_AID_SLUG] = $aidSearchClass->getEuropeanAid();
        }
        if ($aidSearchClass->getIsCallForProject()) {
            $params[self::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT] = $aidSearchClass->getIsCallForProject();
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

        if ($aidSearchClass->getOrganizationType()) {
            $aidParams['organizationType'] = $aidSearchClass->getOrganizationType();
        }
        if ($aidSearchClass->getAudiences()) {
            $aidParams['organizationTypes'] = $aidSearchClass->getAudiences();
        }

        if ($aidSearchClass->getSearchPerimeter()) {
            $aidParams['perimeterFrom'] = $aidSearchClass->getSearchPerimeter();
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

        if ($aidSearchClass->getCategorySearch()) {
            $aidParams['categories'] = $aidSearchClass->getCategorySearch();
        }

        if ($aidSearchClass->getAidTypes()) {
            $aidParams['aidTypes'] = $aidSearchClass->getAidTypes();
        }

        if ($aidSearchClass->getOrderBy()) {
            $aidParams = array_merge($aidParams, $this->handleOrderBy(['orderBy' => $aidSearchClass->getOrderBy()]));
        }

        if ($aidSearchClass->getBackerschoice()) {
            $aidParams['backers'] = $aidSearchClass->getBackerschoice();
        }

        if ($aidSearchClass->getBackerGroup()) {
            $aidParams['backerGroup'] = $aidSearchClass->getBackerGroup();
        }

        if ($aidSearchClass->getApplyBefore()) {
            $aidParams['applyBefore'] = $aidSearchClass->getApplyBefore();
        }

        if ($aidSearchClass->getPrograms()) {
            $aidParams['programs'] = $aidSearchClass->getPrograms();
        }

        if ($aidSearchClass->getAidSteps()) {
            $aidParams['aidSteps'] = $aidSearchClass->getAidSteps();
        }

        if ($aidSearchClass->getAidDestinations()) {
            $aidParams['aidDestinations'] = $aidSearchClass->getAidDestinations();
        }

        if ($aidSearchClass->getIsCharged() !== null) {
            $aidParams['isCharged'] = $aidSearchClass->getIsCharged();
        }

        if ($aidSearchClass->getEuropeanAid()) {
            $aidParams['europeanAid'] = $aidSearchClass->getEuropeanAid();
        }

        if ($aidSearchClass->getIsCallForProject()) {
            $aidParams['isCallForProject'] = $aidSearchClass->getIsCallForProject();
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
        if (isset($params['querystring'])) {
            // on nettoie la chaine car certaines anciennes alertes ont des caractères HTML
            $query = strip_tags(str_replace(['&amp;'], ['&'], urldecode($params['querystring'])));
        } else {
            if ($this->requestStack->getCurrentRequest()) {
                $query = parse_url($this->requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;
            }
        }

        // transforme en tableau
        $queryParams = $this->normalizeQueryParams($this->parseQueryString($query));

        // > les paramètres en query

        // < le user
        $user = $this->userService->getUserLogged();
        // > le user

        /**
         * < OrganizationTypes
         */
        if (isset($queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS])) {
            $organizationTypes = is_array($queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS]) ? $queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS] : [$queryParams[self::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUGS]];
            /** @var OrganizationTypeRepository $organizationTypeRepository */
            $organizationTypeRepository = $this->managerRegistry->getRepository(OrganizationType::class);
            $organizationTypes = $organizationTypeRepository->findCustom([
                'slugs' => $organizationTypes
            ]);
            foreach ($organizationTypes as $organizationType) {
                $aidSearchClass->addAudience($organizationType);
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
                $aidSearchClass->setOrganizationType($organizationType);
            }
        }

        // si pas de paramètre et pas de paramètres dans l'url on pends le type de l'organisation par défaut du user (on viens direcement sur l'url de recherche d'aide ou page d'accueil)
        if (
            !$aidSearchClass->getOrganizationType()
            && empty($queryParams)
            && $user
            && $user->getDefaultOrganization()
            && $user->getDefaultOrganization()->getOrganizationType()
        ) {
            $aidSearchClass->setOrganizationType($user->getDefaultOrganization()->getOrganizationType());
        }

        if (
            is_array($params)
            && array_key_exists('forceOrganizationType', $params)
            && !$aidSearchClass->getOrganizationType()
        ) {
            $aidSearchClass->setOrganizationType($params['forceOrganizationType']);
        }

        if (isset($queryParams['forceOrganizationType']) && $queryParams['forceOrganizationType'] == 'null' && !$aidSearchClass->getOrganizationType()) {
            $aidSearchClass->setOrganizationType(null);
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
                $aidSearchClass->setSearchPerimeter($perimeter);
            }
        }

        // si pas de paramètre on pends le périmètre de l'organisation par défaut du user
        if (
            !$aidSearchClass->getSearchPerimeter()
            && !isset($params['dontUseUserPerimeter'])
            && empty($queryParams)
            && $user
            && $user->getDefaultOrganization()
            && $user->getDefaultOrganization()->getPerimeter()
        ) {
            $aidSearchClass->setSearchPerimeter($user->getDefaultOrganization()->getPerimeter());
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
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = $this->managerRegistry->getRepository(Category::class);
            $categories = $categoryRepository->findCustom(
                [
                    'slugs' => $categorySlugs
                ]
            );
            foreach ($categories as $category) {
                $aidSearchClass->addCategorySearch($category);
            }
        }

        if (isset($queryParams[self::QUERYSTRING_KEY_CATEGORY_IDS])) {
            $categoryIds = is_array($queryParams[self::QUERYSTRING_KEY_CATEGORY_IDS]) ? $queryParams[self::QUERYSTRING_KEY_CATEGORY_IDS] : [$queryParams[self::QUERYSTRING_KEY_CATEGORY_IDS]];
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = $this->managerRegistry->getRepository(Category::class);
            
            $categories = $categoryRepository->findCustom(
                [
                    'ids' => $categoryIds
                ]
            );
            foreach ($categories as $category) {
                $aidSearchClass->addCategorySearch($category);
            }
        }
        /**
        * > Categories
        */


        /**
         * > AidType
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG])) {
            $aidTypeGroup = $this->managerRegistry->getRepository(AidTypeGroup::class)->findOneBy(['slug' => $queryParams[self::QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG]]);
            if ($aidTypeGroup instanceof AidTypeGroup) {
                foreach ($aidTypeGroup->getAidTypes() as $aidType) {
                    $aidSearchClass->addAidType($aidType);
                }
            }
        }

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_TYPE_IDS])) {
            $aidTypeIds = is_array($queryParams[self::QUERYSTRING_KEY_AID_TYPE_IDS]) ? $queryParams[self::QUERYSTRING_KEY_AID_TYPE_IDS] : [$queryParams[self::QUERYSTRING_KEY_AID_TYPE_IDS]];
            /** @var AidTypeRepository $aidTypeRepository */
            $aidTypeRepository = $this->managerRegistry->getRepository(AidType::class);
            $aidTypes = $aidTypeRepository->findCustom(
                [
                    'ids' => $aidTypeIds
                ]
            );
            foreach ($aidTypes as $aidType) {
                $aidSearchClass->addAidType($aidType);
            }
        }

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_STEP_SLUGS])) {
            $aidTypeSlugs = is_array($queryParams[self::QUERYSTRING_KEY_AID_STEP_SLUGS]) ? $queryParams[self::QUERYSTRING_KEY_AID_STEP_SLUGS] : [$queryParams[self::QUERYSTRING_KEY_AID_STEP_SLUGS]];
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
                            $aidSearchClass->addAidType($aidType);
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
                    $aidSearchClass->addAidType($aidType);
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
            /** @var BackerRepository $backerRepository */
            $backerRepository = $this->managerRegistry->getRepository(Backer::class);
            $backers = $backerRepository->findCustom(
                [
                    'ids' => $backersIds
                ]
            );
            foreach ($backers as $backer) {
                $aidSearchClass->addBackerchoice($backer);
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
                $aidSearchClass->setBackerGroup($backerGroup);
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

            // il peu y avoir des slugs dans ce paramètre
            $slugs = [];
            foreach ($queryParams[self::QUERYSTRING_KEY_PROGRAM_IDS] as $idProgram) {
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
            /** @var AidStepRepository $aidStepRepository */
            $aidStepRepository = $this->managerRegistry->getRepository(AidStep::class);
            $aidSteps = $aidStepRepository->findCustom(
                [
                    'slugs' => $aidStepSlugs
                ]
            );
            foreach ($aidSteps as $aidStep) {
                $aidSearchClass->addAidStep($aidStep);
            }
        }

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_STEP_IDS])) {
            $aidStepIds = is_array($queryParams[self::QUERYSTRING_KEY_AID_STEP_IDS]) ? $queryParams[self::QUERYSTRING_KEY_AID_STEP_IDS] : [$queryParams[self::QUERYSTRING_KEY_AID_STEP_IDS]];
            /** @var AidStepRepository $aidStepRepository */
            $aidStepRepository = $this->managerRegistry->getRepository(AidStep::class);
            $aidSteps = $aidStepRepository->findCustom(
                [
                    'ids' => $aidStepIds
                ]
            );
            foreach ($aidSteps as $aidStep) {
                $aidSearchClass->addAidStep($aidStep);
            }
        }
        /**
         * > AidStep
         */

        /**
         * < AidRecurrence
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_RECURRENCE_SLUG])) {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => $queryParams[self::QUERYSTRING_KEY_AID_RECURRENCE_SLUG]]);
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
            /** @var AidDestinationRepository $aidDestinationRepository */
            $aidDestinationRepository = $this->managerRegistry->getRepository(AidDestination::class);
            $aidDestinations = $aidDestinationRepository->findCustom(
                [
                    'slugs' => $aidDestinationSlugs
                ]
            );
            foreach ($aidDestinations as $aidDestination) {
                $aidSearchClass->addAidDestination($aidDestination);
            }
        }

        if (isset($queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_IDS])) {
            $aidDestinationIds = is_array($queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_IDS]) ? $queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_IDS] : [$queryParams[self::QUERYSTRING_KEY_AID_DESTINATION_IDS]];
            /** @var AidDestinationRepository $aidDestinationRepository */
            $aidDestinationRepository = $this->managerRegistry->getRepository(AidDestination::class);
            $aidDestinations = $aidDestinationRepository->findCustom(
                [
                    'ids' => $aidDestinationIds
                ]
            );
            foreach ($aidDestinations as $aidDestination) {
                $aidSearchClass->addAidDestination($aidDestination);
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
            $aidSearchClass->setEuropeanAid((string) $queryParams[self::QUERYSTRING_KEY_EUROPEAN_AID_SLUG]);
        }

        /**
         * > europeanAid
         */

        /**
         * < isCallForProject
         */

        if (isset($queryParams[self::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT])) {
            $isCallForProject = $queryParams[self::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT];
            if (
                trim(strtolower((string) $isCallForProject)) === 'false'
                || trim(strtolower((string) $isCallForProject)) === 'off'
                || (int) $isCallForProject == 0
                ) {
                $aidSearchClass->setIsCharged(false);
            } elseif (
                trim(strtolower((string) $isCallForProject)) === 'true'
                || trim(strtolower((string) $isCallForProject)) === 'on'
                || (int) $isCallForProject == 1
                ) {
                $aidSearchClass->setIsCharged(true);
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
        return $aidSearchClass->getAidTypes() ||
            $aidSearchClass->getBackerschoice() ||
            $aidSearchClass->getApplyBefore() ||
            $aidSearchClass->getPrograms() ||
            $aidSearchClass->getAidSteps() ||
            $aidSearchClass->getAidDestinations() ||
            $aidSearchClass->getIsCharged() !== null ||
            $aidSearchClass->getEuropeanAid() ||
            $aidSearchClass->getIsCallForProject() ||
            $aidSearchClass->getBackerGroup();
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
            'apply_before' => self::QUERYSTRING_KEY_APPLY_BEFORE,
            'published_after' => self::QUERYSTRING_KEY_PUBLISHED_AFTER,
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
            'call_for_projects_only' => self::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT,
            'is_charged' => self::QUERYSTRING_KEY_IS_CHARGED,
            'perimeter' => self::QUERYSTRING_KEY_SEARCH_PERIMETER,
            'project_reference_id' => self::QUERYSTRING_KEY_PROJECT_REFERENCE_ID,
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
            // Supprime les crochets [] ou [0], [1], etc. des clés
            $normalizedKey = preg_replace('/\[\d*\]$/', '', $key);
            // Vérifie si la clé normalisée existe dans le tableau de transition
            $normalizedKey = $transitionKeys[$normalizedKey] ?? $normalizedKey;
            $normalizedParams[$normalizedKey] = $value;
        }
    
        return $normalizedParams;
    }
}
