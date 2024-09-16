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
use App\Repository\Category\CategoryRepository;
use App\Repository\Keyword\KeywordSynonymlistRepository;
use App\Repository\Organization\OrganizationTypeRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Service\User\UserService;
use App\Service\Various\StringService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AidSearchFormService
{
    const CATEGORY_SEARCH_PARAM_NAME = 'categorysearch[]';
    const AID_TYPE_PARAM_NAME = 'aidTypes[]';
    const BACKERS_PARAM_NAME = 'backers[]';
    const PROGRAMS_PARAM_NAME = 'programs[]';
    const AID_STEPS_PARAM_NAME = 'aidSteps[]';
    const AID_DESTINATIONS_PARAM_NAME = 'aidDestinations[]';

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
            $params['organizationType'] = $aidSearchClass->getOrganizationType()->getSlug();
        }
        if ($aidSearchClass->getSearchPerimeter()) {
            $params['searchPerimeter'] = $aidSearchClass->getSearchPerimeter()->getId();
        }
        if ($aidSearchClass->getKeyword()) {
            $params['keyword'] = $aidSearchClass->getKeyword();
        }
        if ($aidSearchClass->getCategorySearch()) {
            $categories = [];
            foreach ($aidSearchClass->getCategorySearch() as $category) {
                $categories[] = $category->getId();
            }
            $params['categorySearch'] = $categories;
        }
        if ($aidSearchClass->isNewIntegration()) {
            $params['newIntegration'] = $aidSearchClass->isNewIntegration();
        }
        if ($aidSearchClass->getOrderBy()) {
            $params['orderBy'] = $aidSearchClass->getOrderBy();
        }
        if ($aidSearchClass->getAidTypes()) {
            $aidTypes = [];
            foreach ($aidSearchClass->getAidTypes() as $aidType) {
                $aidTypes[] = $aidType->getId();
            }
            $params['aidTypes'] = $aidTypes;
        }
        if ($aidSearchClass->getBackerschoice()) {
            $backers = [];
            foreach ($aidSearchClass->getBackerschoice() as $backer) {
                $backers[] = $backer->getId();
            }
            $params['backerschoice'] = $backers;
        }
        if ($aidSearchClass->getBackerGroup()) {
            $params['backerGroup'] = $aidSearchClass->getBackerGroup()->getId();
        }
        if ($aidSearchClass->getApplyBefore()) {
            $params['applyBefore'] = $aidSearchClass->getApplyBefore()->format('Y-m-d');
        }
        if ($aidSearchClass->getPrograms()) {
            $programs = [];
            foreach ($aidSearchClass->getPrograms() as $program) {
                $programs[] = $program->getId();
            }
            $params['programs'] = $programs;
        }
        if ($aidSearchClass->getAidSteps()) {
            $aidSteps = [];
            foreach ($aidSearchClass->getAidSteps() as $aidStep) {
                $aidSteps[] = $aidStep->getSlug();
            }
            $params['aidSteps'] = $aidSteps;
        }
        if ($aidSearchClass->getAidDestinations()) {
            $aidDestinations = [];
            foreach ($aidSearchClass->getAidDestinations() as $aidDestination) {
                $aidDestinations[] = $aidDestination->getSlug();
            }
            $params['aidDestinations'] = $aidDestinations;
        }
        if ($aidSearchClass->getIsCharged()) {
            $params['isCharged'] = $aidSearchClass->getIsCharged();
        }
        if ($aidSearchClass->getEuropeanAid()) {
            $params['europeanAid'] = $aidSearchClass->getEuropeanAid();
        }
        if ($aidSearchClass->getIsCallForProject()) {
            $params['isCallForProject'] = $aidSearchClass->getIsCallForProject();
        }
        if ($aidSearchClass->getProjectReference()) {
            $params['project_reference_id'] = $aidSearchClass->getProjectReference()->getId();
            if (!$aidSearchClass->getKeyword()) {
                $params['keyword'] = $aidSearchClass->getProjectReference()->getName();
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
            $query = strip_tags(str_replace(['&amp;'], ['&'], urldecode((string) $params['querystring'])));
        } else {
            if ($this->requestStack->getCurrentRequest()) {
                $query = parse_url($this->requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;
            }
        }

        $queryParams = [];
        $queryItems = explode('&', (string) $query);

        if (is_array($queryItems)) {
            foreach ($queryItems as $queyItem) {
                $param = explode('=', urldecode($queyItem));
                if (isset($param[0]) && isset($param[1])) {
                    $param[0] = strip_tags((string) $param[0]);
                    $param[1] = strip_tags((string) $param[1]);
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

        // > les paramètres en query

        // < le user
        $user = $this->userService->getUserLogged();
        // > le user

        /**
         * < OrganizationType
         */

        // si ancien paramètre
        if (isset($queryParams['targeted_audiences'])) {
            if (is_array($queryParams['targeted_audiences'])) {
                foreach ($queryParams['targeted_audiences'] as $slugAudience) {
                    $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy(['slug' => $this->stringService->getSlug($slugAudience)]);
                    if ($organizationType instanceof OrganizationType) {
                        $aidSearchClass->addAudience($organizationType);
                    }
                }
            } else {
                $queryParams['targeted_audiences'] = $queryParams['targeted_audiences'][0];
                $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy(['slug' => $this->stringService->getSlug((string) $queryParams['targeted_audiences'])]);
                if ($organizationType instanceof OrganizationType) {
                    $aidSearchClass->setOrganizationType($organizationType);
                }
            }
        }

        // nouveau paramètre
        if (isset($queryParams['organizationType'])) {
            $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy(['slug' => (string) $queryParams['organizationType']]);
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

        // si ancien paramètre
        if (isset($queryParams['perimeter'])) {
            $perimeter = $this->managerRegistry->getRepository(Perimeter::class)->find((int) $queryParams['perimeter']);
            if ($perimeter instanceof Perimeter) {
                $aidSearchClass->setSearchPerimeter($perimeter);
            }
        }

        // nouveau paramètre
        if (isset($queryParams['searchPerimeter'])) {
            $perimeter = $this->managerRegistry->getRepository(Perimeter::class)->find((int) $queryParams['searchPerimeter']);
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
        // si ancien paramètre
        if (isset($queryParams['text'])) {
            $keyword = (string) $queryParams['text'];
        }
        // nouveau paramètre
        if (isset($queryParams['keyword'])) {
            if (is_array($queryParams['keyword'])) {
                $queryParams['keyword'] = trim(implode(' ', $queryParams['keyword']));
            }
            $keyword = (string) $queryParams['keyword'];
        }
        $aidSearchClass->setKeyword($keyword);

        /**
         * > Keyword
         */

        /**
         * > Categories
         */
        if (isset($queryParams['categories'])) {
            if (!is_array($queryParams['categories'])) {
                $queryParams['categories'] = [$queryParams['categories']];
            }
            foreach ($queryParams['categories'] as $categorySlug) {
                $category = $this->managerRegistry->getRepository(Category::class)->findOneBy(['slug' => $categorySlug]);
                if ($category instanceof Category) {
                    $aidSearchClass->addCategorySearch($category);
                }
            }
        }

        if (isset($queryParams[self::CATEGORY_SEARCH_PARAM_NAME])) {
            if (!is_array($queryParams[self::CATEGORY_SEARCH_PARAM_NAME])) {
                $queryParams[self::CATEGORY_SEARCH_PARAM_NAME] = [$queryParams[self::CATEGORY_SEARCH_PARAM_NAME]];
            }
            foreach ($queryParams[self::CATEGORY_SEARCH_PARAM_NAME] as $categoryId) {
                $category = $this->managerRegistry->getRepository(Category::class)->find((int) $categoryId);
                if ($category instanceof Category) {
                    $aidSearchClass->addCategorySearch($category);
                }
            }
        }

        $paramThemes = $queryParams['themes'] ?? null;
        if ($paramThemes && is_array($paramThemes)) {
            foreach ($paramThemes as $paramTheme) {
                $category = $this->managerRegistry->getRepository(Category::class)->findOneBy(['slug' => $paramTheme]);
                if ($category instanceof Category) {
                    $aidSearchClass->addCategorySearch($category);
                }
            }
        }

        /**
         * > Categories
         */

        /**
         * > AidType
         */

        if (isset($queryParams[self::AID_TYPE_PARAM_NAME])) {
            if (!is_array($queryParams[self::AID_TYPE_PARAM_NAME])) {
                $queryParams[self::AID_TYPE_PARAM_NAME] = [$queryParams[self::AID_TYPE_PARAM_NAME]];
            }
            foreach ($queryParams[self::AID_TYPE_PARAM_NAME] as $idAidType) {
                $aidType = $this->managerRegistry->getRepository(AidType::class)->find($idAidType);
                if ($aidType instanceof AidType) {
                    $aidSearchClass->addAidType($aidType);
                }
            }
        }

        if (isset($queryParams['aidTypeGroup'])) {
            $aidTypeGroup = $this->managerRegistry->getRepository(AidTypeGroup::class)->findOneBy(['slug' => $queryParams['aidTypeGroup']]);
            if ($aidTypeGroup instanceof AidTypeGroup) {
                foreach ($aidTypeGroup->getAidTypes() as $aidType) {
                    $aidSearchClass->addAidType($aidType);
                }
            }
        }

        if (isset($queryParams['aidTypeSlug'])) {
            $aidType = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['slug' => $queryParams['aidTypeSlug']]);
            if ($aidType instanceof AidType) {
                $aidSearchClass->addAidType($aidType);
            }
        }

        // via l'api on peu avoir un groupe d'aide
        if (isset($queryParams['aid_type'])) {
            $aidTypeSlugs = [];
            if (is_array($queryParams['aid_type'])) {
                foreach ($queryParams['aid_type'] as $slug) {
                    $aidTypeSlugs[] = str_replace(['_'], ['-'], $slug);
                }
            } else {
                $aidTypeSlugs[] = str_replace(['_'], ['-'], $queryParams['aid_type']);
            }
            $aidTypes = [];
            foreach ($aidTypeSlugs as $slug) {
                if ($slug == AidTypeGroup::SLUG_FINANCIAL) {
                    $aidTypesFromGroup = $this->managerRegistry->getRepository(AidType::class)->findBy([
                        'aidTypeGroup' => $this->managerRegistry->getRepository(AidTypeGroup::class)->findOneBy(['slug' => AidTypeGroup::SLUG_FINANCIAL])
                    ]);
                    foreach ($aidTypesFromGroup as $aidType) {
                        $aidTypes[] = $aidType;
                    }
                } elseif ($slug == AidTypeGroup::SLUG_TECHNICAL) {
                    $aidTypesFromGroup = $this->managerRegistry->getRepository(AidType::class)->findBy([
                        'aidTypeGroup' => $this->managerRegistry->getRepository(AidTypeGroup::class)->findOneBy(['slug' => AidTypeGroup::SLUG_TECHNICAL])
                    ]);
                    foreach ($aidTypesFromGroup as $aidType) {
                        $aidTypes[] = $aidType;
                    }
                } else {
                    $aidType = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['slug' => $slug]);
                    if ($aidType instanceof AidType) {
                        $aidTypes[] = $aidType;
                    }
                }
            }

            foreach ($aidTypes as $aidType) {
                $aidSearchClass->addAidType($aidType);
            }
        }

        // via l'api
        if (isset($queryParams['financial_aids'])) {
            $aidType = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['slug' => (string) $queryParams['financial_aids']]);
            if ($aidType instanceof AidType) {
                $aidSearchClass->addAidType($aidType);
            }
        }

        if (isset($queryParams['technical_aids'])) {
            $aidType = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['slug' => (string) $queryParams['technical_aids']]);
            if ($aidType instanceof AidType) {
                $aidSearchClass->addAidType($aidType);
            }
        }

        /**
         * > AidType
         */

        /**
         * < Backers
         */
        $backers = [];
        foreach ($queryParams as $key => $value) {
            if (strpos($key, 'backers') !== false) {
                if (is_array($value)) {
                    $backers = array_merge($backers, $value);
                } else {
                    $backers[] = $value;
                }
            }
        }

        if ($backers) {
            if (!is_array($backers)) {
                $backers = [$backers];
            }
            foreach ($backers as $idBacker) {
                $backer = $this->managerRegistry->getRepository(Backer::class)->find((int) $idBacker);
                if ($backer instanceof Backer) {
                    $aidSearchClass->addBackerchoice($backer);
                } else {
                    $backer = $this->managerRegistry->getRepository(Backer::class)->findOneBy(['slug' => $idBacker]);
                    if ($backer instanceof Backer) {
                        $aidSearchClass->addBackerchoice($backer);
                    }
                }
            }
        }
        /**
         * > Backers
         */

        /**
         * < BackerGroup
         */
        if (isset($queryParams['backerGroup'])) {
            $backerGroup = $this->managerRegistry->getRepository(BackerGroup::class)->find((int) $queryParams['backerGroup']);
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
        if (isset($queryParams['programs'])) {
            if (!is_array($queryParams['programs'])) {
                $queryParams['programs'] = [$queryParams['programs']];
            }
            foreach ($queryParams['programs'] as $idProgram) {
                $program = $this->managerRegistry->getRepository(Program::class)->find((int) $idProgram);
                if ($program instanceof Program) {
                    $aidSearchClass->addProgram($program);
                } else {
                    $program = $this->managerRegistry->getRepository(Program::class)->findOneBy(['slug' => $idProgram]);
                    if ($program instanceof Program) {
                        $aidSearchClass->addProgram($program);
                    }
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

        if (isset($queryParams['mobilization_step'])) {
            if (!is_array($queryParams['mobilization_step'])) {
                $queryParams['mobilization_step'] = [$queryParams['mobilization_step']];
            }
            foreach ($queryParams['mobilization_step'] as $slugAidStep) {
                $aidStep = $this->managerRegistry->getRepository(AidStep::class)->findOneBy(['slug' => $slugAidStep]);
                if ($aidStep instanceof AidStep) {
                    $aidSearchClass->addAidStep($aidStep);
                }
            }
        }

        /**
         * > AidStep
         */

        /**
         * < AidRecurrence
         */

        if (isset($queryParams['recurrence'])) {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => $queryParams['recurrence']]);
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

        if (isset($queryParams['destinations'])) {
            if (!is_array($queryParams['destinations'])) {
                $queryParams['destinations'] = [$queryParams['destinations']];
            }
            foreach ($queryParams['destinations'] as $slugAidDestination) {
                $aidDestination = $this->managerRegistry->getRepository(AidDestination::class)->findOneBy(['slug' => $slugAidDestination]);
                if ($aidDestination instanceof AidDestination) {
                    $aidSearchClass->addAidDestination($aidDestination);
                }
            }
        }

        /**
         * > AidDestination
         */


        /**
         * < isCharged
         */

        if (isset($queryParams['is_charged'])) {
            if (trim(strtolower((string) $queryParams['is_charged'])) === 'false' || (int) $queryParams['is_charged'] == 0) {
                $aidSearchClass->setIsCharged(false);
            } elseif (trim(strtolower((string) $queryParams['is_charged'])) === 'true' || (int) $queryParams['is_charged'] == 1) {
                $aidSearchClass->setIsCharged(true);
            }
        }

        /**
         * > isCharged
         */

        /**
         * < europeanAid
         */

        if (isset($queryParams['european_aid'])) {
            $aidSearchClass->setEuropeanAid((string) $queryParams['european_aid']);
        }

        /**
         * > europeanAid
         */

        /**
         * < isCallForProject
         */

        if (
            isset($queryParams['call_for_projects_only'])
            && $queryParams['call_for_projects_only'] == 'on'
        ) {
            $aidSearchClass->setIsCallForProject(true);
        }

        if (
            isset($queryParams['call_for_projects_only'])
            && (string) $queryParams['call_for_projects_only'] == 'true'
        ) {
            $aidSearchClass->setIsCallForProject(true);
        }
        if (
            isset($queryParams['call_for_projects_only'])
            && (string) $queryParams['call_for_projects_only'] == 'false'
        ) {
            $aidSearchClass->setIsCallForProject(false);
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
        if (isset($queryParams['project_reference_id'])) {
            $projectReference = $this->managerRegistry->getRepository(ProjectReference::class)->find((int) $queryParams['project_reference_id']);
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

    public function convertQuerystringToParams(string $querystring): array // NOSONAR too complex
    {
        $params = [];
        // transforme la querystring en tableau
        $items = explode('&', urldecode($querystring));
        foreach ($items as $item) {
            $explode = explode('=', $item);
            if (!isset($explode[0]) || !isset($explode[1])) {
                continue;
            }
            if (!isset($params[$explode[0]])) {
                $params[$explode[0]] = $explode[1];
            } else {
                if (!is_array($params[$explode[0]])) {
                    $oldValue = $params[$explode[0]];
                    $params[$explode[0]] = [$oldValue, $explode[1]];
                } else {
                    $params[$explode[0]][] = $explode[1];
                }
            }
        }

        // converti les noms des clés
        $keysMapping = [
            self::CATEGORY_SEARCH_PARAM_NAME => 'categories',
            'perimeter' => 'searchPerimeter',
            'targeted_audiences' => 'organizationType',
            'programs' => self::PROGRAMS_PARAM_NAME,
            'aid_type' => 'aidTypeSlugs',
            'text' => 'keyword',
            'backers' => self::BACKERS_PARAM_NAME, // ancien système utilise le schéma {id]-{slug}
            'apply_before' => 'applyBefore',
            'mobilization_step' => self::AID_STEPS_PARAM_NAME,
            'destinations' => self::AID_DESTINATIONS_PARAM_NAME,
            'european_aid' => 'europeanAid'
        ];
        foreach ($params as $key => $value) {
            if (isset($keysMapping[$key])) {
                $params[$keysMapping[$key]] = $value;
                unset($params[$key]);
            }
        }

        // les noms qui peuvent varier
        $keysMapping = [
            'categorysearch' => 'categories',
        ];
        foreach ($params as $key => $value) {
            foreach ($keysMapping as $keyMapping => $keyMappingValue) {
                if (strpos($key, $keyMapping) !== false) {
                    $params[$keyMappingValue] = $value;
                    unset($params[$key]);
                }
            }
        }

        // force certains paramètres en tableau
        $keysForceArray = [
            'categories',
            self::AID_TYPE_PARAM_NAME,
            self::BACKERS_PARAM_NAME,
            self::PROGRAMS_PARAM_NAME,
            self::AID_STEPS_PARAM_NAME,
            self::AID_DESTINATIONS_PARAM_NAME,
        ];
        foreach ($params as $key => $param) {
            if (in_array($key, $keysForceArray) && !is_array($params[$key])) {
                $params[$key] = [$param];
            }
        }

        // gestion de prolbème avec les tableaux, ex: aidTypes[] ou aidTypes
        $keysMapping = [
            'programs' => self::PROGRAMS_PARAM_NAME,
            'backers' => self::BACKERS_PARAM_NAME,
            'aidTypes' => self::AID_TYPE_PARAM_NAME,
            'aidSteps' => self::AID_STEPS_PARAM_NAME,
            'aidDestinations' => self::AID_DESTINATIONS_PARAM_NAME,
        ];
        foreach ($params as $key => $value) {
            foreach ($keysMapping as $keyMapping => $keyMappingValue) {
                if (strpos($key, $keyMapping) !== false) {
                    $params[$keyMappingValue] = $value;
                }
            }
        }

        // clé qu'on veu pas garder
        $keysToDelete = [
            '_token'
        ];
        foreach ($params as $key => $param) {
            if (in_array($key, $keysToDelete)) {
                unset($params[$key]);
            }
        }

        // clé sans values
        foreach ($params as $key => $param) {
            if ($param == '') {
                unset($params[$key]);
            }
        }

        // conversion string / int en entity
        if (isset($params['organizationType']) && is_string($params['organizationType'])) {
            $organizationType = $this->organizationTypeRepository->findOneBy(['slug' => $this->stringService->getSlug($params['organizationType'])]);
            if ($organizationType instanceof OrganizationType) {
                $params['organizationType'] = $organizationType;
            } else {
                unset($params['organizationType']);
            }
        }

        if (isset($params['searchPerimeter'])) {
            $perimeter = $this->perimeterRepository->find((int) $params['searchPerimeter']);
            if ($perimeter instanceof Perimeter) {
                $params['perimeterFrom'] = $perimeter;
                unset($params['searchPerimeter']);
            }
        }

        if (isset($params['categories']) && is_array($params['categories'])) {
            $categories = [];
            foreach ($params['categories'] as $idCategory) {
                $category = $this->categoryRepository->find((int) $idCategory);

                if (!$category instanceof Category) {
                    $category = $this->categoryRepository->findOneBy(['slug' => (string) $idCategory]);
                }
                if ($category instanceof Category) {
                    $categories[] = $category;
                }
            }
            $params['categories'] = $categories;
        }

        if (isset($params['aidTypeSlugs']) && is_array($params['aidTypeSlugs'])) {
            $aidTypes = [];
            foreach ($params['aidTypeSlugs'] as $aidTypeSlug) {
                $aidType = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['slug' => $aidTypeSlug]);
                if ($aidType instanceof AidType) {
                    $aidTypes[] = $aidType;
                }
            }
            $params['aidTypes'] = $aidTypes;
            unset($params['aidTypeSlugs']);
        }
        if (isset($params[self::AID_TYPE_PARAM_NAME]) && is_array($params[self::AID_TYPE_PARAM_NAME])) {
            $aidTypes = [];
            foreach ($params[self::AID_TYPE_PARAM_NAME] as $idAidType) {
                $aidType = $this->managerRegistry->getRepository(AidType::class)->find((int) $idAidType);
                if ($aidType instanceof AidType) {
                    $aidTypes[] = $aidType;
                }
            }
            $params['aidTypes'] = $aidTypes;
            unset($params[self::AID_TYPE_PARAM_NAME]);
        }

        if (isset($params[self::BACKERS_PARAM_NAME]) && is_array($params[self::BACKERS_PARAM_NAME])) {
            $backers = [];
            foreach ($params[self::BACKERS_PARAM_NAME] as $idBacker) {
                $backer = $this->managerRegistry->getRepository(Backer::class)->find((int) $idBacker);
                if ($backer instanceof Backer) {
                    $backers[] = $backer;
                }
            }
            $params['backers'] = $backers;
            unset($params[self::BACKERS_PARAM_NAME]);
        }

        // conversion string en datetime
        if (isset($params['applyBefore'])) {
            try {
                $applyBefore = new \DateTime(date($params['applyBefore']));
                $params['applyBefore'] = $applyBefore;
                unset($applyBefore);
            } catch (\Exception $e) {
                unset($params['applyBefore']);
            }
        }

        if (isset($params[self::PROGRAMS_PARAM_NAME]) && is_array($params[self::PROGRAMS_PARAM_NAME])) {
            $programs = [];
            foreach ($params[self::PROGRAMS_PARAM_NAME] as $idProgram) {
                // si entier
                if ($idProgram == (int) $idProgram) {
                    $program = $this->managerRegistry->getRepository(Program::class)->find((int) $idProgram);
                    if ($program instanceof Program) {
                        $programs[] = $program;
                    }
                }
                if (is_string($idProgram)) {
                    $program = $this->managerRegistry->getRepository(Program::class)->findOneBy(['slug' => $idProgram]);
                    if ($program instanceof Program) {
                        $programs[] = $program;
                    }
                }
            }
            $params['programs'] = $programs;
            unset($params[self::PROGRAMS_PARAM_NAME]);
        }

        if (isset($params[self::AID_STEPS_PARAM_NAME]) && is_array($params[self::AID_STEPS_PARAM_NAME])) {
            $aidSteps = [];
            foreach ($params[self::AID_STEPS_PARAM_NAME] as $idAidStep) {
                $aidStep = null;
                if (is_int($idAidStep)) {
                    $aidStep = $this->managerRegistry->getRepository(AidStep::class)->find((int) $idAidStep);
                } elseif (is_string($idAidStep)) {
                    $aidStep = $this->managerRegistry->getRepository(AidStep::class)->findOneBy(['slug' => (string) $idAidStep]);
                }
                if ($aidStep instanceof AidStep) {
                    $aidSteps[] = $aidStep;
                }
            }
            $params['aidSteps'] = $aidSteps;
            unset($params[self::AID_STEPS_PARAM_NAME]);
        }

        if (isset($params[self::AID_DESTINATIONS_PARAM_NAME]) && is_array($params[self::AID_DESTINATIONS_PARAM_NAME])) {
            $aidDestinations = [];
            foreach ($params[self::AID_DESTINATIONS_PARAM_NAME] as $idAidDestination) {
                $aidDestination = null;
                if (is_int($idAidDestination)) {
                    $aidDestination = $this->managerRegistry->getRepository(AidDestination::class)->find((int) $idAidDestination);
                } elseif (is_string($idAidDestination)) {
                    $aidDestination = $this->managerRegistry->getRepository(AidDestination::class)->findOneBy(['slug' => (string) $idAidDestination]);
                }
                if ($aidDestination instanceof AidDestination) {
                    $aidDestinations[] = $aidDestination;
                }
            }
            $params['aidDestinations'] = $aidDestinations;
            unset($params[self::AID_DESTINATIONS_PARAM_NAME]);
        }

        // isCharged
        if (isset($params['isCharged'])) {
            if ((int) $params['isCharged'] == 1) {
                $params['isCharged'] = true;
            } elseif ((int) $params['isCharged'] == 0) {
                $params['isCharged'] = false;
            }
        }
        if (isset($params['is_charged'])) {
            if (trim(strtolower((string) $params['is_charged'])) === 'false') {
                $params['isCharged'] = false;
            } elseif (trim(strtolower((string) $params['is_charged'])) === 'true') {
                $params['isCharged'] = true;
            }
            unset($params['is_charged']);
        }

        // isCallForProject
        if (isset($params['isCallForProject'])) {
            if ((int) $params['isCallForProject'] == 1) {
                $params['isCallForProject'] = true;
            } elseif ((int) $params['isCallForProject'] == 0) {
                $params['isCallForProject'] = false;
            }
        }
        if (isset($params['call_for_projects_only'])) {
            if (trim(strtolower((string) $params['call_for_projects_only'])) == 'on') {
                $params['isCallForProject'] = true;
            } elseif (trim(strtolower((string) $params['call_for_projects_only'])) == 'off') {
                $params['isCallForProject'] = false;
            }
            unset($params['call_for_projects_only']);
        }

        return $params;
    }

    /**
     * Complète le tableau de paramètres pour la requête qui va récuprer les aides
     *
     * @param Form $formAidSearch
     * @return array
     */
    public function completeAidParams(Form $formAidSearch): array
    { // NOSONAR too complex
        $aidParams = [];
        if ($formAidSearch->get('organizationType')->getData()) {
            $aidParams['organizationType'] = $formAidSearch->get('organizationType')->getData();
        }
        if ($formAidSearch->get('searchPerimeter')->getData()) {
            $aidParams['perimeterFrom'] = $formAidSearch->get('searchPerimeter')->getData();
        }

        if ($formAidSearch->get('keyword')->getData()) {
            $aidParams['keyword'] = $formAidSearch->get('keyword')->getData();
        }

        if ($formAidSearch->get('categorysearch')->getData()) {
            $aidParams['categories'] = $formAidSearch->get('categorysearch')->getData();
        }

        if ($formAidSearch->has('orderBy') && $formAidSearch->get('orderBy')->getData()) {
            $aidParams['orderBy'] = $formAidSearch->get('orderBy')->getData();
            $aidParams = $this->handleOrderBy($aidParams);
        }

        if ($formAidSearch->has('aidTypes') && $formAidSearch->get('aidTypes')->getData()) {
            $aidParams['aidTypes'] = $formAidSearch->get('aidTypes')->getData();
        }

        if ($formAidSearch->get('backers')->getData()) {
            $aidParams['backers'] = $formAidSearch->get('backers')->getData();
        }

        if ($formAidSearch->get('applyBefore')->getData()) {
            $aidParams['applyBefore'] = $formAidSearch->get('applyBefore')->getData();
        }

        if ($formAidSearch->get('aidSteps')->getData()) {
            $aidParams['aidSteps'] = $formAidSearch->get('aidSteps')->getData();
        }

        if ($formAidSearch->has('programs') && $formAidSearch->get('programs')->getData()) {
            $aidParams['programs'] = $formAidSearch->get('programs')->getData();
        }

        if ($formAidSearch->get('aidDestinations')->getData()) {
            $aidParams['aidDestinations'] = $formAidSearch->get('aidDestinations')->getData();
        }

        if ($formAidSearch->get('isCharged')->getData() !== null) {
            $aidParams['isCharged'] = $formAidSearch->get('isCharged')->getData();
        }

        if ($formAidSearch->has('europeanAid') &&  $formAidSearch->get('europeanAid')->getData() !== null) {
            $aidParams['europeanAid'] = $formAidSearch->get('europeanAid')->getData();
        }

        // seulement si coché
        if ($formAidSearch->get('isCallForProject')->getData()) {
            $aidParams['isCallForProject'] = $formAidSearch->get('isCallForProject')->getData();
        }

        return $aidParams;
    }

    /**
     * Détermine si le formulaire doit être affiché en entier ou non, selon si un des champs de la liste à été rempli.
     *
     * @param Form $formAidSearch
     * @return boolean
     */
    public function setShowExtended(Form $formAidSearch): bool
    {
        $showExtended = false;
        $fields = ['aidTypes', 'backers', 'applyBefore', 'programs', 'aidSteps', 'aidDestinations', 'isCharged', 'europeanAid', 'isCallForProject', 'backerGroup'];
        foreach ($fields as $field) {
            if ($formAidSearch->has($field) && $formAidSearch->get($field)->getData()) {
                if ($formAidSearch->get($field)->getData() instanceof ArrayCollection) {
                    if (count($formAidSearch->get($field)->getData()) > 0) {
                        $showExtended = true;
                    }
                } else {
                    $showExtended = true;
                }
            }
        }

        return $showExtended;
    }

    public function setShowExtendedV2(AidSearchClass $aidSearchClass): bool
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
}
