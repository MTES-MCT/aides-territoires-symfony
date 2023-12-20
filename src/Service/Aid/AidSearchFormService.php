<?php

namespace App\Service\Aid;

use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Form\Aid\AidSearchType;
use App\Repository\Category\CategoryRepository;
use App\Repository\Keyword\KeywordSynonymlistRepository;
use App\Repository\Organization\OrganizationTypeRepository;
use App\Repository\Perimeter\PerimeterRepository;
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
        protected StringService $stringService
    )
    {
        
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
}