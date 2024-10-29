<?php

namespace App\Controller\Cartography;

use App\Controller\FrontController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Perimeter\Perimeter;
use App\Form\Cartography\CartographySearchType;
use App\Form\Program\CountySelectType;
use App\Repository\Aid\AidRepository;
use App\Repository\Aid\AidTypeGroupRepository;
use App\Repository\Aid\AidTypeRepository;
use App\Repository\Backer\BackerRepository;
use App\Repository\Category\CategoryRepository;
use App\Repository\Category\CategoryThemeRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Program\ProgramRepository;
use App\Service\Aid\AidService;
use App\Service\Perimeter\PerimeterService;
use App\Service\Various\StringService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartographyController extends FrontController
{
    #[Route('/cartographie/', name: 'app_cartography_cartography')]
    public function index(
        PerimeterRepository $perimeterRepository,
        BackerRepository $backerRepository,
        ProgramRepository $programRepository,
        RequestStack $requestStack,
        StringService $stringService
    ): Response {
        // les infos départements pour la carte
        $counties = $perimeterRepository->findCounties();
        $departmentsData = [];
        foreach ($counties as $county) {
            $departmentsData[] = [
                'code' => $county->getCode(),
                'name' => $county->getName(),
                'slug' => $stringService->getSlug($county->getName()),
                'backers_count' => $county->getBackersCount()
            ];
        }

        // formulaire choix département
        $formCounties = $this->createForm(CountySelectType::class);
        $formCounties->handleRequest($requestStack->getCurrentRequest());
        if ($formCounties->isSubmitted()) {
            if ($formCounties->isValid()) {
                $county = $perimeterRepository->findOneBy([
                    'code' => $formCounties->get('county')->getData(),
                    'scale' => Perimeter::SCALE_COUNTY
                ]);
                if ($county instanceof Perimeter) {
                    return $this->redirectToRoute('app_cartography_detail', [
                        'code' => $county->getCode(),
                        'slug' => $stringService->getSlug($county->getName())
                    ]);
                }
            }
        }

        // nb porteur
        $nbBackers = $backerRepository->countWithAids();

        // nb programs
        $nbPrograms = $programRepository->countCustom();

        // fil arianne
        $this->breadcrumb->add(
            'Cartographie',
            null
        );

        // rendu template
        return $this->render('cartography/cartography/index.html.twig', [
            'controller_name' => 'CartographyController',
            'departmentsData' => $departmentsData,
            'nbBackers' =>  $nbBackers,
            'nbPrograms' => $nbPrograms,
            'formCounties' => $formCounties->createView(),
        ]);
    }

    #[Route(
        '/cartographie/{code}-{slug}/porteurs/',
        name: 'app_cartography_detail',
        requirements: ['code' => '[0-9A-Za-z]+', 'slug' => '[a-zA-Z0-9\-_]+']
    )]
    public function detail(
        $code,
        $slug,
        PerimeterRepository $perimeterRepository,
        BackerRepository $backerRepository,
        RequestStack $requestStack,
        StringService $stringService,
        PerimeterService $perimeterService,
        CategoryRepository $categoryRepository,
        CategoryThemeRepository $categoryThemeRepository,
        AidService $aidService,
        AidRepository $aidRepository,
        AidTypeGroupRepository $aidTypeGroupRepository
    ): Response {
        // departement courant
        $current_dep = $perimeterRepository->findOneBy([
            'code' => $code,
            'scale' => Perimeter::SCALE_COUNTY
        ]);
        if (!$current_dep instanceof Perimeter) {
            $this->addFlash(
                FrontController::FLASH_ERROR,
                'Département non trouvé'
            );
            return $this->redirectToRoute('app_cartography_cartography');
        }

        // formulaire de recherche de porteur d'aide
        $formBackerSearch = $this->createForm(
            CartographySearchType::class,
            null,
            [
                'action' => $this->generateUrl(
                    'app_cartography_detail',
                    [
                        'code' => $current_dep->getCode(),
                        'slug' => $stringService->getSlug($current_dep->getName())
                    ]
                ),
                'method' => 'GET',
                'forceDepartement' => $current_dep
            ]
        );
        $formBackerSearch->handleRequest($requestStack->getCurrentRequest());

        // les porteurs d'aides du département
        $backerParams = [
            'active' => true,
            'perimeterFrom' => $current_dep,
            'orderBy' => [
                'sort' => 'b.name',
                'order' => 'ASC'
            ]
        ];
        if ($formBackerSearch->get('organizationType')->getData()) {
            $backerParams['organizationType'] = $formBackerSearch->get('organizationType')->getData();
        }
        if ($formBackerSearch->get('aidTypeGroup')->getData()) {
            $backerParams['aidTypeGroup'] = $formBackerSearch->get('aidTypeGroup')->getData();
        }
        if ($formBackerSearch->get('categorysearch')->getData()) {
            if (
                isset($formBackerSearch->get('categorysearch')->getData()['customChoices'])
                && count($formBackerSearch->get('categorysearch')->getData()['customChoices']) > 0
            ) {
                $backerParams['categoryIds'] =
                    $formBackerSearch->get('categorysearch')->getData()['customChoices'];
            }
        }
        if ($formBackerSearch->get('scaleGroup')->getData()) {
            $backerParams['perimeterScales'] =
                $perimeterService->getScalesFromGroup(
                    $formBackerSearch->get('scaleGroup')->getData()
                );
        }
        if ($formBackerSearch->get('backerCategory')->getData()) {
            $backerParams['backerCategory'] = $formBackerSearch->get('backerCategory')->getData();
        }

        // la liste des backers
        $backers = $backerRepository->findBackerWithAidInCounty($backerParams);

        // actions selon type d'aide
        $aidTypeGroupSlug = null;
        $aidTypeGroup = $formBackerSearch->get('aidTypeGroup')->getData();
        if ($aidTypeGroup instanceof AidTypeGroup) {
            $aidTypeGroupSlug = $aidTypeGroup->getSlug();
        }

        // Tableau des ids de périmètres contenus dans le département courant
        $perimeterIds = $perimeterRepository->getIdPerimetersContainedIn(['perimeter' => $current_dep]);
        $perimeterIds[] = $current_dep->getId();

        // les paramètres pour les aides
        $aidsParams = [
            'showInSearch' => true,
            'perimeterFromIds' => $perimeterIds,
            'organizationType' => $backerParams['organizationType'] ?? null,
            'aidTypeGroup' => $backerParams['aidTypeGroup'] ?? null,
            'categoryIds' => $backerParams['categoryIds'] ?? null,
            'perimeterScales' => $backerParams['perimeterScales'] ?? null,
            'backerCategory' => $backerParams['backerCategory'] ?? null,
            'noPostPopulate' => true,
        ];
        switch ($aidTypeGroupSlug) {
            case AidTypeGroup::SLUG_FINANCIAL:
                $tableTemplate = 'cartography/cartography/_backers_table_financial.html.twig';
                break;

            case AidTypeGroup::SLUG_TECHNICAL:
                $tableTemplate = 'cartography/cartography/_backers_table_technical.html.twig';
                break;

            default:
                $tableTemplate = 'cartography/cartography/_backers_table_default.html.twig';
                break;
        }

        // Si il y a un filtre sur les categoryTheme
        $categoryThemesSelected = new ArrayCollection();
        if (isset($backerParams['categoryIds'])) {
            $categories = $categoryRepository->findCustom([
                'ids' => $backerParams['categoryIds']
            ]);
            foreach ($categories as $category) {
                if (
                    $category->getCategoryTheme() !== null
                    && !$categoryThemesSelected->contains($category->getCategoryTheme())
                ) {
                    $categoryThemesSelected->add($category->getCategoryTheme());
                }
            }
        }

        // on charge toute les thematiques
        $categoryThemes = $categoryThemeRepository->findAll();
        $categoryThemesById = [];
        foreach ($categoryThemes as $categoryTheme) {
            $categoryThemesById[$categoryTheme->getId()] = $categoryTheme;
        }

        // Les groupes de type d'aides
        $aidTypeGroupTechnical = $aidTypeGroupRepository->findOneBy(['slug' => AidTypeGroup::SLUG_TECHNICAL]);
        $aidTypeGroupFinancial = $aidTypeGroupRepository->findOneBy(['slug' => AidTypeGroup::SLUG_FINANCIAL]);

        // assigne les aides aux backers
        foreach ($backers as $backer) {
            $aidsParams['backer'] = $backer;
            // défini les aides lives, à partir de quoi on pourra récupérer les financières, techniques, les thématiques
            $backer->setAidsLive($aidService->searchAids($aidsParams));

            /** @var Aid $aid */
            $aidsLiveIds = [];
            foreach ($backer->getAidsLive() as $aid) {
                $aidsLiveIds[] = $aid->getId();
            }

            // on récupère les thématiques des aides
            $categoryThemesIds = $aidRepository->getCategoryThemesIdsFromIds($aidsLiveIds);
            $backerCategoryThemes = new ArrayCollection();
            foreach ($categoryThemesIds as $categoryThemeId) {
                if (isset($categoryThemesById[$categoryThemeId])) {
                    $backerCategoryThemes->add($categoryThemesById[$categoryThemeId]);
                }
            }
            $backer->setAidsThematics($backerCategoryThemes);
            $iterator = $backer->getAidsThematics()->getIterator();
            $iterator->uasort(function ($a, $b) {
                return ($a->getSlug() < $b->getSlug()) ? -1 : 1;
            });
            $backer->setAidsThematics(new ArrayCollection(iterator_to_array($iterator)));

            // on récupère les groupes de types d'aides des aides
            $backer->setNbAidsByTypeGroupSlug([
                AidTypeGroup::SLUG_FINANCIAL => $aidRepository->countAidsFromIds(
                    $aidsLiveIds,
                    ['aidTypeGroup' => $aidTypeGroupFinancial]
                ),
                AidTypeGroup::SLUG_TECHNICAL => $aidRepository->countAidsFromIds(
                    $aidsLiveIds,
                    ['aidTypeGroup' => $aidTypeGroupTechnical]
                ),
            ]);
        }

        // fil arianne
        $this->breadcrumb->add(
            'Cartographie',
            $this->generateUrl('app_cartography_cartography')
        );
        $this->breadcrumb->add(
            $current_dep->getCode() . " " . $current_dep->getName(),
            null
        );

        // rendu template
        return $this->render('cartography/cartography/detail.html.twig', [
            'current_dept'  => $current_dep,
            'backers' => $backers,
            'formBackerSearch' => $formBackerSearch,
            'tableTemplate' => $tableTemplate,
            'categoryThemesSelected' => $categoryThemesSelected
        ]);
    }
}
