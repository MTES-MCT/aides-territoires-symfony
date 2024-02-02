<?php

namespace App\Service\Aid;

use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Reference\ProjectReference;
use App\Form\Aid\AidSearchType;
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
    )
    {
        
    }

    public function convertAidSearchClassToAidParams(AidSearchClass $aidSearchClass): array
    {
        $aidParams = [];

        if ($aidSearchClass->getOrganizationType()) {
            $aidParams['organizationType'] = $aidSearchClass->getOrganizationType();
        }

        if ($aidSearchClass->getSearchPerimeter()) {
            $aidParams['perimeterFrom'] = $aidSearchClass->getSearchPerimeter();
        }

        if ($aidSearchClass->getKeyword()) {
            $aidParams['keyword'] = $aidSearchClass->getKeyword();
        }

        if ($aidSearchClass->getCategorySearch()) {
            $aidParams['categories'] = $aidSearchClass->getCategorySearch();
        }

        if ($aidSearchClass->getAidTypes()) {
            $aidParams['aidTypes'] = $aidSearchClass->getAidTypes();
        }

        if ($aidSearchClass->getOrderBy()) {
            $aidParams['orderBy'] = $this->handleOrderBy(['orderBy' => $aidSearchClass->getOrderBy()]);
        }

        if ($aidSearchClass->getBackerschoice()) {
            $aidParams['backers'] = $aidSearchClass->getBackerschoice();
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

        if ($aidSearchClass->getIsCharged()) {
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

        return $aidParams;
    }

    public function getAidSearchClass(?AidSearchClass $aidSearchClass = null, ?array $params = null): AidSearchClass
    {
        if (!$aidSearchClass) {
            $aidSearchClass = new AidSearchClass();
        }

        // < les paramètres en query
        if (isset($params['querystring'])) {
            $query = $params['querystring'];
        } else {
            $query = parse_url($this->requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;
        }
        $queryParams = [];
        $queryItems = explode('&', $query);

        if (is_array($queryItems)) {
            foreach ($queryItems as $queyItem) {
                $param = explode('=', urldecode($queyItem));
                if (isset($param[0]) && isset($param[1])) {
                    if (isset($queryParams[$param[0]]) && is_array($queryParams[$param[0]])) {
                        $queryParams[$param[0]][] = $param[1];
                    } else if (isset($queryParams[$param[0]]) && !is_array($queryParams[$param[0]])) {
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
                $queryParams['targeted_audiences'] = $queryParams['targeted_audiences'][0];
            }
            $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy(['slug' => $this->stringService->getSlug((string) $queryParams['targeted_audiences'])]);
            if ($organizationType instanceof OrganizationType) {
                $aidSearchClass->setOrganizationType($organizationType);
            }
        }

        // nouveau paramètre
        if (isset($queryParams['organizationType'])) {
            $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy(['slug' => (string) $queryParams['organizationType']]);
            if ($organizationType instanceof OrganizationType) {
                $aidSearchClass->setOrganizationType($organizationType);
            }
        }

        // si pas de paramètre on pends le type de l'organisation par défaut du user
        if (!$aidSearchClass->getOrganizationType()) {
            if ($user && $user->getDefaultOrganization() && $user->getDefaultOrganization()->getOrganizationType()) {
                $aidSearchClass->setOrganizationType($user->getDefaultOrganization()->getOrganizationType());
            }
        }

        if (is_array($params)) {
            if (array_key_exists('forceOrganizationType', $params)) {
                $aidSearchClass->setOrganizationType($params['forceOrganizationType']);
            }
        }
        
        if (isset($queryParams['forceOrganizationType']) && $queryParams['forceOrganizationType'] == 'null') {
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
        if (!$aidSearchClass->getSearchPerimeter() && !isset($params['dontUseUserPerimeter'])) {
            if ($user && $user->getDefaultOrganization() && $user->getDefaultOrganization()->getPerimeter()) {
                $aidSearchClass->setSearchPerimeter($user->getDefaultOrganization()->getPerimeter());
            }
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

        /**
         * > Categories
        */

        /**
         * > AidType
        */
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
        /**
         * > AidType
        */
        
        /**
         * < Backers
        */
        $backers = [];
        foreach ($queryParams as $key => $value) {
            if (strpos($key, 'backers') !== false) {
                $backers[] = $value;
                break;
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
            if ($queryParams['is_charged'] === 'False') {
                $aidSearchClass->setIsCharged(false);
            } else if ($queryParams['is_charged'] === 'True') {
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

        if (isset($queryParams['call_for_projects_only'])) {
            if ($queryParams['call_for_projects_only'] == 'on') {
                $aidSearchClass->setIsCallForProject(true);
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
            }
        }

        return $aidParams;
    }

    public function convertQuerystringToParams(string $querystring): array
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
            'categorysearch[]' => 'categories',
            'perimeter' => 'searchPerimeter',
            'targeted_audiences' => 'organizationType',
            'programs' => 'programs[]',
            'aid_type' => 'aidTypeSlugs',
            'text' => 'keyword',
            'backers' => 'backers[]', // ancien système utilise le schéma {id]-{slug}
            'apply_before' => 'applyBefore',
            'mobilization_step' => 'aidSteps[]',
            'destinations' => 'aidDestinations[]',
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
            // 'programSlugs',
            'aidTypes[]',
            'backers[]',
            'programs[]',
            'aidSteps[]',
            'aidDestinations[]',
        ];
        foreach ($params as $key => $param) {
            if (in_array($key, $keysForceArray) && !is_array($params[$key])) {
                $params[$key] = [$param];
            }
        }

        // gestion de prolbème avec les tableaux, ex: aidTypes[] ou aidTypes
        $keysMapping = [
            'programs' => 'programs[]',
            'backers' => 'backers[]',
            'aidTypes' => 'aidTypes[]',
            'aidSteps' => 'aidSteps[]',
            'aidDestinations' => 'aidDestinations[]',
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
        if (isset($params['aidTypes[]']) && is_array($params['aidTypes[]'])) {
            $aidTypes = [];
            foreach ($params['aidTypes[]'] as $idAidType) {
                $aidType = $this->managerRegistry->getRepository(AidType::class)->find((int) $idAidType);
                if ($aidType instanceof AidType) {
                    $aidTypes[] = $aidType;
                }
            }
            $params['aidTypes'] = $aidTypes;
            unset($params['aidTypes[]']);
        }

        if (isset($params['backers[]']) && is_array($params['backers[]'])) {
            $backers = [];
            foreach ($params['backers[]'] as $idBacker) {
                $backer = $this->managerRegistry->getRepository(Backer::class)->find((int) $idBacker);
                if ($backer instanceof Backer) {
                    $backers[] = $backer;
                }
            }
            $params['backers'] = $backers;
            unset($params['backers[]']);
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

        if (isset($params['programs[]']) && is_array($params['programs[]'])) {
            $programs = [];
            foreach ($params['programs[]'] as $idProgram) {
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
            unset($params['programs[]']);
        }

        if (isset($params['aidSteps[]']) && is_array($params['aidSteps[]'])) {
            $aidSteps = [];
            foreach ($params['aidSteps[]'] as $idAidStep) {
                $aidStep = null;
                if (is_int($idAidStep)) {
                    $aidStep = $this->managerRegistry->getRepository(AidStep::class)->find((int) $idAidStep);
                } else if (is_string($idAidStep)) {
                    $aidStep = $this->managerRegistry->getRepository(AidStep::class)->findOneBy(['slug' => (string) $idAidStep]);
                }
                if ($aidStep instanceof AidStep) {
                    $aidSteps[] = $aidStep;
                }
            }
            $params['aidSteps'] = $aidSteps;
            unset($params['aidSteps[]']);
        }

        if (isset($params['aidDestinations[]']) && is_array($params['aidDestinations[]'])) {
            $aidDestinations = [];
            foreach ($params['aidDestinations[]'] as $idAidDestination) {
                $aidDestination = null;
                if (is_int($idAidDestination)) {
                    $aidDestination = $this->managerRegistry->getRepository(AidDestination::class)->find((int) $idAidDestination);
                } else if (is_string($idAidDestination)) {
                    $aidDestination = $this->managerRegistry->getRepository(AidDestination::class)->findOneBy(['slug' => (string) $idAidDestination]);
                }
                if ($aidDestination instanceof AidDestination) {
                    $aidDestinations[] = $aidDestination;
                }
            }
            $params['aidDestinations'] = $aidDestinations;
            unset($params['aidDestinations[]']);
        }

        // isCharged
        if (isset($params['isCharged'])) {
            if ((int) $params['isCharged'] == 1) {
                $params['isCharged'] = true;
            } else if ((int) $params['isCharged'] == 0) {
                $params['isCharged'] = false;
            }
        }
        if (isset($params['is_charged'])) {
            if ($params['is_charged'] === 'False') {
                $params['isCharged'] = false;
            } else if ($params['is_charged'] === 'True') {
                $params['isCharged'] = true;
            }
            unset($params['is_charged']);
        }

        // isCallForProject
        if (isset($params['isCallForProject'])) {
            if ((int) $params['isCallForProject'] == 1) {
                $params['isCallForProject'] = true;
            } else if ((int) $params['isCallForProject'] == 0) {
                $params['isCallForProject'] = false;
            }
        }
        if (isset($params['call_for_projects_only'])) {
            if ($params['call_for_projects_only'] == 'on') {
                $params['isCallForProject'] = true;
            } else if ($params['call_for_projects_only'] == 'off') {
                $params['isCallForProject'] = false;
            }
            unset($params['call_for_projects_only']);
        }

        return $params;
    }

    /**
     * Gère les paramètres du formulaire de recherche d'aide pour le préremplir selon la query
     *
     * @return array
     */
    public function completeFormAidSearchParams($queryString=null): array
    {
        $formAidSearchParams = [];

        // converti depuis la queryString
        if ($queryString) {
            $aidParams = $this->convertQuerystringToParams($queryString);

            if (isset($aidParams['organizationType'])) {
                $formAidSearchParams['forceOrganizationType'] = $aidParams['organizationType'];
            }
            if (isset($aidParams['perimeterFrom'])) {
                $formAidSearchParams['forcePerimeter'] = $aidParams['perimeterFrom'];
            }
            if (isset($aidParams['textSearch'])) {
                $formAidSearchParams['forceKeyword'] = $aidParams['textSearch'];
            }
            if (isset($aidParams['keyword'])) {
                $formAidSearchParams['forceKeyword'] = $aidParams['keyword'];
            }
            $categories = $aidParams['categorySlugs'] ?? $aidParams['categories'] ?? null;
            if (isset($categories) && is_array($categories)) {
                foreach ($categories as $idCategory) {
                    if ($idCategory instanceof Category) {
                        $categoriesSearched[] = $idCategory;
                        continue;
                    }
                    $category = $this->categoryRepository->find($idCategory);
                    if (!$category instanceof Category) {
                        $category = $this->categoryRepository->findOneBy(['slug' => $idCategory]);
                    }
                    if ($category instanceof Category) {
                        $categoriesSearched[] = $category;
                    } 
                }
                if (count($categoriesSearched) > 0) {
                    $formAidSearchParams['forceCategorySearch'] = $categoriesSearched;
                }
            }

            if (isset($aidParams['programs'])) {
                $formAidSearchParams['forcePrograms'] = $aidParams['programs'];
            }




            if (isset($aidParams['backers'])) {
                $formAidSearchParams['forceBackers'] = $aidParams['backers'];
            }


            if (isset($aidParams['categorySlugs'])) {
                unset($aidParams['categorySlugs']);
            }
            if (isset($aidParams['categories'])) {
                unset($aidParams['categories']);
            }
        }

        //-------------------------------------------------------------
        // regarde si anciens paramètres
        $idPerimeter = $this->requestStack->getCurrentRequest()->get('perimeter', null);
        if ($idPerimeter) {
            $perimeter = $this->perimeterRepository->find($idPerimeter);
            if ($perimeter instanceof Perimeter) {
                $formAidSearchParams['forcePerimeter'] = $perimeter;
                unset($perimeter);
            }
        }
        
        $targetedAudiences = $this->requestStack->getCurrentRequest()->get('targeted_audiences', null);
        if ($targetedAudiences) {
            $organizationType = $this->organizationTypeRepository->findOneBy(['slug' => $targetedAudiences]);
            if ($organizationType instanceof OrganizationType) {
                $formAidSearchParams['forceOrganizationType'] = $organizationType;
                unset($organizationType);
            }
        }

        $text = $this->requestStack->getCurrentRequest()->get('text', null);
        if ($text) {
            $keywordSynonymlist = $this->keywordSynonymlistRepository->find((int) $text);
            if ($keywordSynonymlist instanceof KeywordSynonymlist) {
                $formAidSearchParams['forceKeyword'] = $keywordSynonymlist;
                unset($keywordSynonymlist);
            }
        }

        // les themes categories
        $categorySlugs = [];
        $categoriesSearched = [];
        $infos = parse_url($this->requestStack->getCurrentRequest()->getRequestUri());
        if (isset($infos['query'])) {
            $items = explode('&', $infos['query']);
            foreach ($items as $item) {
                $param = explode('=', $item);
                if (isset($param[0]) && $param[0] == 'categories') {
                    if (isset($param[1])) {
                        $categorySlugs[] = $param[1];
                    }
                }
            }

            foreach ($categorySlugs as $categorySlug) {
                $category = $this->categoryRepository->findOneBy(['slug' => $categorySlug]);
    
                if ($category instanceof Category) {
                    $categoriesSearched[] = $category;
                } 
            }
        }
        
        if (count($categoriesSearched) > 0) {
            $formAidSearchParams['forceCategorySearch'] = $categoriesSearched;
        }

        //-------------------------------------------------------------
        // Nouveaux paramètres
        $formTest = $this->formFactory->create(AidSearchType::class, null, ['extended' => true]);

        $infos = parse_url($this->requestStack->getCurrentRequest()->getRequestUri());
        if (isset($infos['query'])) {
            $queryItems = explode('&', $infos['query']);
            if (is_array($queryItems)) {
                foreach ($queryItems as $key => $value) {
                    $param = explode('=', urldecode($value));
                    $formKey = preg_replace('/\[.*\]|/', '', $param[0]);
                    if ($formTest->has($formKey) && isset($param[1])) {
                        $formAidSearchParams = $this->convertQueryItemtoData($formAidSearchParams, $formKey, $param[0], $param[1]);
                    }
                }
            }
        }
        
        return $formAidSearchParams;
    }

    /**
     * Transforme les items de la query en data pour le formulaire de recherche d'aide
     *
     * @param array $formAidSearchParams
     * @param string $formKey
     * @param string $queryItem
     * @return mixed
     */
    private function convertQueryItemtoData(array $formAidSearchParams, string $formKey, string $realKey, string $queryItem): mixed
    {
        try {
            switch ($formKey) {
                case 'organizationType':
                    $organization = $this->managerRegistry->getRepository(Organization::class)->findOneBy([
                        'slug' => $queryItem
                    ]);
                    if ($organization instanceof Organization) {
                        $formAidSearchParams['forceOrganizationType'] = $organization;
                    }
                    break;

                case 'searchPerimeter':
                    $perimeter = $this->managerRegistry->getRepository(Perimeter::class)->find($queryItem);
                    if ($perimeter instanceof Perimeter) {
                        $formAidSearchParams['forcePerimeter'] = $perimeter;
                    }
                    break;

                case 'keyword':
                    $formAidSearchParams['forceKeyword'] = strip_tags($queryItem);
                    break;

                // se gère en automatique via les paramètres GET
                case 'categorysearch':
                    break;

                // se gère en automatique via les paramètres GET
                case 'aidTypes':
                    break;

                case 'backers':
                    if (!isset($formAidSearchParams['forceBackers'])) {
                        $formAidSearchParams['forceBackers'] = [];
                    }
                    $backer = $this->managerRegistry->getRepository(Backer::class)->find($queryItem);
                    if ($backer instanceof Backer) {
                        $formAidSearchParams['forceBackers'][] = $backer;
                    }
                    break;
    
                case 'applyBefore':
                    $date = new \DateTime(date($queryItem));
                    $formAidSearchParams['forceApplyBefore'] = $date;
                    break;
                    
                case 'programs':
                    $program = $this->managerRegistry->getRepository(Program::class)->findOneBy(['slug' => $queryItem]);
                    if ($program instanceof Program) {
                        $formAidSearchParams['forcePrograms'][] = $program;
                    }
                    break;
                
                case 'aidSteps':
                    if (!isset($formAidSearchParams['forceAidSteps'])) {
                        $formAidSearchParams['forceAidSteps'] = [];
                    }
                    $aidStep = $this->managerRegistry->getRepository(AidStep::class)->find($queryItem);
                    if ($aidStep instanceof AidStep) {
                        $formAidSearchParams['forceAidSteps'][] = $aidStep;
                    }
                    break;
                    
                case 'aidDestinations':
                    if (!isset($formAidSearchParams['forceAidDestinations'])) {
                        $formAidSearchParams['forceAidDestinations'] = [];
                    }
                    $aidDestination = $this->managerRegistry->getRepository(AidDestination::class)->find($queryItem);
                    if ($aidDestination instanceof AidDestination) {
                        $formAidSearchParams['forceAidDestinations'][] = $aidDestination;
                    }
                    break;

                case 'isCharged':
                    $formAidSearchParams['forceIsCharged'] = strip_tags($queryItem);
                    break;
                    
                case 'europeanAid':
                    $formAidSearchParams['forceEuropeanAid'] = strip_tags($queryItem);
                    break;

                case 'isCallForProject':
                    // uniquement si coché
                    if ($queryItem) {
                        $formAidSearchParams['forceIsCallForProject'] = (bool) $queryItem;
                    }
                    break;
                        
                default:
                    break;
            }
    
            return $formAidSearchParams;
        } catch (\Exception $e) {
            return $formAidSearchParams;
        }
    }

    /**
     * Complète le tableau de paramètres pour la requête qui va récuprer les aides
     *
     * @param Form $formAidSearch
     * @return array
     */
    public function completeAidParams(Form $formAidSearch) : array {
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
        $fields = ['aidTypes', 'backers', 'applyBefore', 'programs', 'aidSteps', 'aidDestinations', 'isCharged', 'europeanAid', 'isCallForProject'];
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
        if (
            $aidSearchClass->getAidTypes() ||
            $aidSearchClass->getBackerschoice() ||
            $aidSearchClass->getApplyBefore() ||
            $aidSearchClass->getPrograms() ||
            $aidSearchClass->getAidSteps() ||
            $aidSearchClass->getAidDestinations() ||
            $aidSearchClass->getIsCharged() !== null ||
            $aidSearchClass->getEuropeanAid() ||
            $aidSearchClass->getIsCallForProject()
        ) {
            return true;
        } else {
            return false;
        }
    }
}