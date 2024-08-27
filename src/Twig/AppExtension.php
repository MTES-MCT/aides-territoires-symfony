<?php

namespace App\Twig;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
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
use App\Service\Category\CategoryService;
use App\Service\Matomo\MatomoService;
use App\Service\Perimeter\PerimeterService;
use App\Service\Reference\KeywordReferenceService;
use App\Service\User\UserService;
use App\Service\Various\Breadcrumb;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Nelmio\SecurityBundle\EventListener\ContentSecurityPolicyListener;

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
        private ContentSecurityPolicyListener $contentSecurityPolicyListener,
        private KeywordReferenceService $keywordReferenceService
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
        ];
    }

    public function htmlDecode($string)
    {
        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    }

    public function myTruncate(string $string, int $length = 200): string
    {
        return $this->stringService->truncate($string, $length);
    }

    public function mySlug(string $string) : string {
        return $this->stringService->getSlug($string);
    }

    public function getPerimeterSmartName(?Perimeter $perimeter) : string {
        if (!$perimeter instanceof Perimeter) {
            return '';
        }
        return $this->perimeterService->getSmartName($perimeter);
    }
    
    public function perimeterSmartRegionNames(?Perimeter $perimeter) : string {
        if (!$perimeter instanceof Perimeter) {
            return '';
        }
        return $this->perimeterService->getSmartRegionNames($perimeter);
    }

    public function aidStatusDisplay(string $slug) : string {
        switch ($slug) {
            case Aid::STATUS_DRAFT:
                return 'Brouillon';
                break;

            case Aid::STATUS_REVIEWABLE:
                return 'En revue';
                break;

            case Aid::STATUS_PUBLISHED:
                return 'Publiée';
                break;
                
            case Aid::STATUS_DELETED:
                return 'Supprimée';
                break;

            case Aid::STATUS_MERGED:
                return 'Fusionnée';
                break;

            default:
                return '';
        }
    }

    public function alertFrequencyDisplay(string $slug) : string {
        foreach (Alert::FREQUENCIES as $frequency) {
            if ($frequency['slug'] == $slug) {
                return $frequency['name'] ?? '';
            }
        }
        return '';
    }

    public function projectStepDisplay(string $string) : string {
        foreach (Project::PROJECT_STEPS as $step) {
            if ($step['slug'] == $string) {
                return $step['name'];
            }
        }
        return '';
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
            new TwigFunction('isDateTime', [$this, 'isDateTime'])
        ];
    }

    public function getBreadcrumbItems()
    {
        return $this->breadcrumb->getItems();
    }

    public function getParameter(string $parameterName) : ?string {
        try {
            return $this->paramService->get($parameterName);
        } catch (\Exception $e) {
            return null;
        }
    }


    public function isUserGranted($user, $role): bool
    {
        return $this->userService->isUserGranted($user, $role);
    }

    public function optimizeHtmlFromWysiwyg(string $html): string
    {
        try {
            $html = $this->addLazyToImg($html);
            $html = $this->addNonceToInlineCss($html);
            return $html;
        } catch (\Exception $e) {
            return $html;
        }
    }

    public function addMailtoToEmailLinks($html)
    {
        $dom = new \DOMDocument();
        // pour garder le utf-8
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD);
        $x = new \DOMXPath($dom);

        foreach($x->query("//a") as $node)
        {
            $href = $node->getAttribute('href');
            // Vérifie si le href est une adresse e-mail
            if (preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $href)) {
                // Ajoute mailto: au début du href
                $node->setAttribute('href', 'mailto:' . $href);
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

    public function addNonceToInlineCss($html)
    {
        $dom = new \DOMDocument();
        // pour garder le utf-8
        $dom->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'), LIBXML_HTML_NODEFDTD);
        $x = new \DOMXPath($dom);
        
        foreach($x->query("//*[@style]") as $node)
        {
            $styles = explode(';', $node->getAttribute('style'));

            $classesToAdd = [];
            foreach ($styles as $style) {
                if ($style == 'text-align: left') {
                    $classesToAdd[] = 'text-left';
                } elseif ($style == 'text-align: right') {
                    $classesToAdd[] = 'text-right';
                } elseif ($style == 'text-align: center') {
                    $classesToAdd[] = 'text-center';
                }
            }

            // Récupérer l'attribut de classe actuel
            $currentClass = $node->getAttribute('class');

            // Ajouter les nouvelles classes
            $classesToAdd = implode(' ', $classesToAdd);
            if ($currentClass !== '') {
                $newClass = $currentClass . ' ' . $classesToAdd;
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


    public function addLazyToImg($html)
    {
        $dom = new \DOMDocument();
        // pour garder le utf-8
        $dom->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'), LIBXML_HTML_NODEFDTD);
        $x = new \DOMXPath($dom);
        
        foreach($x->query("//img") as $node)
        {
            $node->setAttribute("loading","lazy");
        }
        return substr($dom->saveHTML(), 12, -15);
    }

    public function getPerimeterScale($scale) : ?array {
        return $this->perimeterService->getScale($scale);
    }

    public function categoriesToMetas(ArrayCollection|array $categories) : array {
        return $this->categoryService->categoriesToMetas($categories);
    }

    public function getEntityById(string $entityName, int $id) : mixed {
        try {
            return $this->managerRegistry->getRepository('App\Entity\\'.$entityName)->find($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserSibEmailId(?User $user) : string {
        return $this->userService->getSibEmailId($user);
    }

    public function getMatomoGoalId(): ?string
    {
        return $this->matomoService->getGoal();
    }

    public function getKeywordReferenceAndSynonyms(?string $keyword): string{
        if (!$keyword) {
            return '';
        }

        return $this->keywordReferenceService->getKeywordReferenceAndSynonyms($keyword);
    }

    public function getUserPublicProjectLatestView(?User $user, ?Project $project): ?LogPublicProjectView {
        if (!$user || !$project) {
            return null;
        }
        return $this->userService->getPublicProjectLatestView($user, $project);
    }

    public function getImportAidManualDatas() : array
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
        $categoryRepositoy = $this->managerRegistry->getRepository(Category::class);
        $categoryNames = $categoryRepositoy->getNames();
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
  
    public function isDateTime($date): bool {
        return $date instanceof \DateTime;
    }
}
