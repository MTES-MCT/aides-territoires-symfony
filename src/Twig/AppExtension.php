<?php

namespace App\Twig;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidInstructor;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Alert\Alert;
use App\Entity\Category\Category;
use App\Entity\Log\LogPublicProjectView;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Project\Project;
use App\Entity\Reference\ProjectReference;
use App\Entity\User\User;
use App\Repository\Aid\AidDestinationRepository;
use App\Repository\Aid\AidRecurrenceRepository;
use App\Repository\Aid\AidStepRepository;
use App\Repository\Aid\AidTypeRepository;
use App\Repository\Category\CategoryRepository;
use App\Repository\Organization\OrganizationTypeRepository;
use App\Repository\Program\ProgramRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Service\Aid\AidService;
use App\Service\Category\CategoryService;
use App\Service\Category\CategoryTheme;
use App\Service\Matomo\MatomoService;
use App\Service\Perimeter\PerimeterService;
use App\Service\Reference\KeywordReferenceService;
use App\Service\Site\AbTestService;
use App\Service\User\UserService;
use App\Service\Various\Breadcrumb;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension // NOSONAR too much methods
{
    public function __construct(
        public Breadcrumb $breadcrumb,
        private ParamService $paramService,
        private UserService $userService,
        private PerimeterService $perimeterService,
        private CategoryService $categoryService,
        private ManagerRegistry $managerRegistry,
        private StringService $stringService,
        private MatomoService $matomoService,
        private KeywordReferenceService $keywordReferenceService,
        private AidService $aidService,
        private AbTestService $abTestService,
    ) {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('htmlDecode', [$this, 'htmlDecode']),
            new TwigFilter('myTruncate', [$this, 'myTruncate']),
            new TwigFilter('mySlug', [$this, 'mySlug']),
            new TwigFilter('perimeterSmartRegionNames', [$this, 'perimeterSmartRegionNames']),
            new TwigFilter('getPerimeterSmartName', [$this, 'getPerimeterSmartName']),
            new TwigFilter('aidStatusDisplay', [$this, 'aidStatusDisplay']),
            new TwigFilter('alertFrequencyDisplay', [$this, 'alertFrequencyDisplay']),
            new TwigFilter('projectStepDisplay', [$this, 'projectStepDisplay']),
            new TwigFilter('secondsToMinutes', [$this, 'secondsToMinutes'])
        ];
    }

    public function htmlDecode(string $string): string
    {
        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    }

    public function myTruncate(string $string, int $length = 200): string
    {
        return $this->stringService->truncate($string, $length);
    }

    public function mySlug(string $string): string
    {
        return $this->stringService->getSlug($string);
    }

    public function getPerimeterSmartName(?Perimeter $perimeter): string
    {
        if (!$perimeter instanceof Perimeter) {
            return '';
        }

        return $this->perimeterService->getSmartName($perimeter);
    }

    public function perimeterSmartRegionNames(?Perimeter $perimeter): string
    {
        if (!$perimeter instanceof Perimeter) {
            return '';
        }

        return $this->perimeterService->getSmartRegionNames($perimeter);
    }

    public function aidStatusDisplay(string $slug): string
    {
        $statusDisplay = [
            Aid::STATUS_DRAFT => 'Brouillon',
            Aid::STATUS_REVIEWABLE => 'En revue',
            Aid::STATUS_PUBLISHED => 'Publiée',
            Aid::STATUS_DELETED => 'Supprimée',
            Aid::STATUS_MERGED => 'Fusionnée',
        ];

        return $statusDisplay[$slug] ?? '';
    }

    public function alertFrequencyDisplay(string $slug): string
    {
        foreach (Alert::FREQUENCIES as $frequency) {
            if ($frequency['slug'] == $slug) {
                return $frequency['name'];
            }
        }

        return '';
    }

    public function projectStepDisplay(string $string): string
    {
        foreach (Project::PROJECT_STEPS as $step) {
            if ($step['slug'] == $string) {
                return $step['name'];
            }
        }

        return '';
    }

    public function secondsToMinutes(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d min %d sec', $minutes, $remainingSeconds);
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getBreadcrumbItems', [$this, 'getBreadcrumbItems']),
            new TwigFunction('getParameter', [$this, 'getParameter']),
            new TwigFunction('isUserGranted', [$this, 'isUserGranted']),
            new TwigFunction('optimizeHtmlFromWysiwyg', [$this, 'optimizeHtmlFromWysiwyg']),
            new TwigFunction('addMailtoToEmailLinks', [$this, 'addMailtoToEmailLinks']),
            new TwigFunction('addNonceToInlineCss', [$this, 'addNonceToInlineCss']),
            new TwigFunction('getPerimeterScale', [$this, 'getPerimeterScale']),
            new TwigFunction('categoriesToMetas', [$this, 'categoriesToMetas']),
            new TwigFunction('getEntityById', [$this, 'getEntityById']),
            new TwigFunction('getUserSibEmailId', [$this, 'getUserSibEmailId']),
            new TwigFunction('getMatomoGoalId', [$this, 'getMatomoGoalId']),
            new TwigFunction('getKeywordReferenceAndSynonyms', [$this, 'getKeywordReferenceAndSynonyms']),
            new TwigFunction('getUserPublicProjectLatestView', [$this, 'getUserPublicProjectLatestView']),
            new TwigFunction('getImportAidManualDatas', [$this, 'getImportAidManualDatas']),
            new TwigFunction('isDateTime', [$this, 'isDateTime']),
            new TwigFunction('orderAidFinancerByBackerName', [$this, 'orderAidFinancerByBackerName']),
            new TwigFunction('orderAidInstructorByBackerName', [$this, 'orderAidInstructorByBackerName']),
            new TwigFunction('isAidInUserFavorites', [$this, 'isAidInUserFavorites']),
            new TwigFunction('shouldShowTestVersion', [$this, 'shouldShowTestVersion']),
        ];
    }

    /**
     * @return array<array{text: string, url: ?string}>
     */
    public function getBreadcrumbItems(): array
    {
        return $this->breadcrumb->getItems();
    }

    public function getParameter(string $parameterName): ?string
    {
        try {
            return $this->paramService->get($parameterName);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isUserGranted(?User $user, string $role): bool
    {
        return $this->userService->isUserGranted($user, $role);
    }

    public function optimizeHtmlFromWysiwyg(string $html): string
    {
        try {
            $html = $this->addLazyToImg($html);
            $html = $this->addAltToImg($html);
            $html = $this->addNonceToInlineCss($html);
            $html = $this->encapsulateTables($html);

            return $html;
        } catch (\Exception $e) {
            return $html;
        }
    }

    /**
     * on va encapsuler les tables qui ne le sont pas déjà dans
     * <div class="fr-table fr-table--no-scroll">
     *      <div class="fr-table__wrapper">
     *              <div class="fr-table__container">
     *                  <div class="fr-table__content">
     *                  </div>
     *              </div>
     *      </div>
     * </div>
     * Pour coller au style DSFR.
     */
    public function encapsulateTables(string $html): string
    {
        $dom = new \DOMDocument();
        // pour garder le utf-8
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD);
        $x = new \DOMXPath($dom);

        /** @var \DOMElement $node */
        foreach ($x->query('//table') as $node) {
            $wrapper = $dom->createElement('div');
            $wrapper->setAttribute('class', 'fr-table');

            $wrapperWrapper = $dom->createElement('div');
            $wrapperWrapper->setAttribute('class', 'fr-table__wrapper');

            $wrapperContainer = $dom->createElement('div');
            $wrapperContainer->setAttribute('class', 'fr-table__container');

            $wrapperContent = $dom->createElement('div');
            $wrapperContent->setAttribute('class', 'fr-table__content');

            $wrapperContainer->appendChild($wrapperContent);
            $wrapperWrapper->appendChild($wrapperContainer);
            $wrapper->appendChild($wrapperWrapper);

            $node->parentNode->replaceChild($wrapper, $node);
            $wrapperContent->appendChild($node);
        }

        return substr($dom->saveHTML(), 12, -15);
    }

    public function addMailtoToEmailLinks(string $html): string
    {
        // Utiliser tidy pour nettoyer le HTML
        if (extension_loaded('tidy')) {
            $tidy = new \tidy();
            $config = [
                'clean' => true,
                'output-html' => true,
                'show-body-only' => false,
                'wrap' => 0,
            ];
            $tidy->parseString($html, $config, 'utf8');
            $tidy->cleanRepair();
            $html = $tidy->value;
        }

        $dom = new \DOMDocument();
        // pour garder le utf-8
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD);
        $x = new \DOMXPath($dom);

        /** @var \DOMElement $node */
        foreach ($x->query('//a') as $node) {
            $href = $node->getAttribute('href');
            // Vérifie si le href est une adresse e-mail
            if (preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $href)) {
                // Ajoute mailto: au début du href
                $node->setAttribute('href', 'mailto:'.$href);
            }
        }

        // Sélectionner uniquement le contenu intérieur de la balise <body>
        $body = $x->query('//body')->item(0);
        $newHtml = '';
        foreach ($body->childNodes as $childNode) {
            $newHtml .= $dom->saveHTML($childNode);
        }

        return $newHtml;
    }

    public function addNonceToInlineCss(string $html): string
    {
        $dom = new \DOMDocument();
        // pour garder le utf-8
        $dom->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'), LIBXML_HTML_NODEFDTD);
        $x = new \DOMXPath($dom);

        /** @var \DOMElement $node */
        foreach ($x->query('//*[@style]') as $node) {
            $styles = explode(';', $node->getAttribute('style'));

            $classesToAdd = [];
            foreach ($styles as $style) {
                if ('text-align: left' == $style) {
                    $classesToAdd[] = 'text-left';
                } elseif ('text-align: right' == $style) {
                    $classesToAdd[] = 'text-right';
                } elseif ('text-align: center' == $style) {
                    $classesToAdd[] = 'text-center';
                }
            }

            // Récupérer l'attribut de classe actuel
            $currentClass = $node->getAttribute('class');

            // Ajouter les nouvelles classes
            $classesToAdd = implode(' ', $classesToAdd);
            if ('' !== $currentClass) {
                $newClass = $currentClass.' '.$classesToAdd;
            } else {
                $newClass = $classesToAdd;
            }

            // Mettre à jour l'attribut de classe du nœud
            $node->setAttribute('class', $newClass);
        }

        // Sélectionner uniquement le contenu intérieur de la balise <body>
        $body = $x->query('//body')->item(0);
        $newHtml = '';
        foreach ($body->childNodes as $childNode) {
            $newHtml .= $dom->saveHTML($childNode);
        }

        return $newHtml;
    }

    public function addLazyToImg(string $html): string
    {
        $dom = new \DOMDocument();
        // pour garder le utf-8
        $dom->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'), LIBXML_HTML_NODEFDTD);
        $x = new \DOMXPath($dom);

        /** @var \DOMElement $node */
        foreach ($x->query('//img') as $node) {
            $node->setAttribute('loading', 'lazy');
        }

        return substr($dom->saveHTML(), 12, -15);
    }

    public function addAltToImg(string $html): string
    {
        $dom = new \DOMDocument();
        // pour garder le utf-8
        $dom->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'), LIBXML_HTML_NODEFDTD);
        $x = new \DOMXPath($dom);

        /** @var \DOMElement $node */
        foreach ($x->query('//img') as $node) {
            // Vérifie si l'attribut alt existe
            if (!$node->hasAttribute('alt')) {
                $node->setAttribute('alt', '');
            }
        }

        return substr($dom->saveHTML(), 12, -15);
    }

    /**
     * @return array{scale: int, slug: string, name: string}|null
     */
    public function getPerimeterScale(string $scale): ?array
    {
        return $this->perimeterService->getScale((int) $scale);
    }

    /**
     * @param ArrayCollection<int, Category>|array<int, Category> $categories
     *
     * @return array<int, array{
     *     categoryTheme: \App\Entity\Category\CategoryTheme,
     *     categories: array<int, \App\Entity\Category\Category>
     * }>
     */
    public function categoriesToMetas(ArrayCollection|array $categories): array
    {
        return $this->categoryService->categoriesToMetas($categories);
    }

    public function getUserSibEmailId(?User $user): string
    {
        return $this->userService->getSibEmailId($user);
    }

    public function getMatomoGoalId(): ?int
    {
        return $this->matomoService->getGoal();
    }

    public function getKeywordReferenceAndSynonyms(?string $keyword): string
    {
        if (!$keyword) {
            return '';
        }

        return $this->keywordReferenceService->getKeywordReferenceAndSynonyms($keyword);
    }

    public function getUserPublicProjectLatestView(?User $user, ?Project $project): ?LogPublicProjectView
    {
        if (!$user || !$project) {
            return null;
        }

        return $this->userService->getPublicProjectLatestView($user, $project);
    }

    /**
     * @return array{
     *     programs: string[],
     *     organizationTypes: string[],
     *     aidTypes: string[],
     *     categories: string[],
     *     aidRecurrences: string[],
     *     aidSteps: string[],
     *     aidDestinations: string[],
     *     projectReferences: string[]
     * }
     */
    public function getImportAidManualDatas(): array
    {
        $datas = [];

        /** @var ProgramRepository $programRepository */
        $programRepository = $this->managerRegistry->getRepository(Program::class);
        $programNames = $programRepository->getNames();
        $datas['programs'] = $programNames;

        /** @var OrganizationTypeRepository $organizationTypeRepository */
        $organizationTypeRepository = $this->managerRegistry->getRepository(OrganizationType::class);
        $organizationTypeNames = $organizationTypeRepository->getNames();
        $datas['organizationTypes'] = $organizationTypeNames;

        /** @var AidTypeRepository $aidTypeRepository */
        $aidTypeRepository = $this->managerRegistry->getRepository(AidType::class);
        $aidTypeNames = $aidTypeRepository->getNames();
        $datas['aidTypes'] = $aidTypeNames;

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->managerRegistry->getRepository(Category::class);
        $categoryNames = $categoryRepository->getNames();
        $datas['categories'] = $categoryNames;

        /** @var AidRecurrenceRepository $aidRecurrenceRepository */
        $aidRecurrenceRepository = $this->managerRegistry->getRepository(AidRecurrence::class);
        $aidRecurrenceNames = $aidRecurrenceRepository->getNames();
        $datas['aidRecurrences'] = $aidRecurrenceNames;

        /** @var AidStepRepository $aidStepRepository */
        $aidStepRepository = $this->managerRegistry->getRepository(AidStep::class);
        $aidStepNames = $aidStepRepository->getNames();
        $datas['aidSteps'] = $aidStepNames;

        /** @var AidDestinationRepository $aidDestinationRepository */
        $aidDestinationRepository = $this->managerRegistry->getRepository(AidDestination::class);
        $aidDestinationNames = $aidDestinationRepository->getNames();
        $datas['aidDestinations'] = $aidDestinationNames;

        /** @var ProjectReferenceRepository $projectReferenceRepository */
        $projectReferenceRepository = $this->managerRegistry->getRepository(ProjectReference::class);
        $projectReferenceNames = $projectReferenceRepository->getNames();
        $datas['projectReferences'] = $projectReferenceNames;

        return $datas;
    }

    public function isDateTime(mixed $date): bool
    {
        return $date instanceof \DateTime;
    }

    /**
     * @param Collection<int, AidFinancer> $aidFinancers
     *
     * @return Collection<int, AidFinancer>
     */
    public function orderAidFinancerByBackerName(Collection $aidFinancers): Collection
    {
        $aidFinancers = $aidFinancers->toArray();

        usort($aidFinancers, function (AidFinancer $a, AidFinancer $b) {
            $nameA = $this->stringService->normalizeString($a->getBacker()->getName());
            $nameB = $this->stringService->normalizeString($b->getBacker()->getName());

            return strcmp($nameA, $nameB);
        });

        return new ArrayCollection($aidFinancers);
    }

    /**
     * @param Collection<int, AidInstructor> $aidInstructors
     *
     * @return Collection<int, AidInstructor>
     */
    public function orderAidInstructorByBackerName(Collection $aidInstructors): Collection
    {
        $aidInstructors = $aidInstructors->toArray();

        usort($aidInstructors, function (AidInstructor $a, AidInstructor $b) {
            $nameA = $this->stringService->normalizeString($a->getBacker()->getName());
            $nameB = $this->stringService->normalizeString($b->getBacker()->getName());

            return strcmp($nameA, $nameB);
        });

        return new ArrayCollection($aidInstructors);
    }

    public function isAidInUserFavorites(?User $user, ?Aid $aid): bool
    {
        return $this->aidService->isAidInUserFavorites($user, $aid);
    }

    public function shouldShowTestVersion(string $abTestName): bool
    {
        return $this->abTestService->shouldShowTestVersion($abTestName);
    }
}
