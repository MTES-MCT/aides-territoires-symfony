<?php

namespace App\Service\Log;

use App\Entity\Aid\Aid;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\Category\CategoryTheme;
use App\Entity\Log\LogAccountRegisterFromNextPageWarningClickEvent;
use App\Entity\Log\LogAidApplicationUrlClick;
use App\Entity\Log\LogAidCreatedsFolder;
use App\Entity\Log\LogAidOriginUrlClick;
use App\Entity\Log\LogAidSearchTemp;
use App\Entity\Log\LogAidViewTemp;
use App\Entity\Log\LogBackerView;
use App\Entity\Log\LogBlogPostView;
use App\Entity\Log\LogBlogPromotionPostClick;
use App\Entity\Log\LogBlogPromotionPostDisplay;
use App\Entity\Log\LogProgramView;
use App\Entity\Log\LogProjectValidatedSearch;
use App\Entity\Log\LogPublicProjectSearch;
use App\Entity\Log\LogPublicProjectView;
use App\Entity\Organization\Organization;
use App\Entity\User\User;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LogService
{
    public const AID_SEARCH = 'aidSearch';
    public const AID_VIEW = 'aidView';
    public const BACKER_VIEW = 'backerView';
    public const BLOG_POST_VIEW = 'blogPostView';
    public const BLOG_PROMOTION_POST_CLICK = 'blogPromotionPostClick';
    public const BLOG_PROMOTION_POST_DISPLAY = 'blogPromotionPostDisplay';
    public const PROGRAM_VIEW = 'programView';
    public const PROJECT_VALIDATED_SEARCH = 'projectValidatedSearch';
    public const PROJECT_PUBLIC_SEARCH = 'projectPublicSearch';
    public const PROJECT_PUBLIC_VIEW = 'projectPublicView';
    public const LAST_LOG_AID_SEARCH_ID = 'last_logAidSearchId';

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private LoggerInterface $loggerInterface,
        private RequestStack $requestStack,
        private UserService $userService,
    ) {
    }

    /**
     * @param array<mixed>|null $params
     */
    public function log(// NOSONAR too complex
        ?string $type,
        ?array $params,
    ): void {
        try {
            switch ($type) {
                case 'register-from-next-page-warning':
                    $querystring = '';
                    if (is_array($params)) {
                        foreach ($params as $key => $param) {
                            if ('_token' == $key) { // pas besoin de stocker le tocken
                                continue;
                            }
                            $querystring .= $key . '=' . $param . '&';
                        }
                        $querystring = substr($querystring, 0, -1); // on enlève le dernier & (qui est en trop)
                    }
                    if ('' == trim($querystring)) {
                        $querystring = null;
                    }
                    $log = new LogAccountRegisterFromNextPageWarningClickEvent();
                    $log->setQuerystring($querystring);

                    break;

                case 'originUrl':
                    $log = new LogAidOriginUrlClick();
                    $log->setQuerystring($params['querystring']);
                    $log->setSource($this->getSiteFromHost($params['host']));
                    $source = $this->getLogAidSearchSourceInSession();
                    if ($source) {
                        $log->setSource($source);
                    }
                    $aid = null;
                    if (isset($params['aidSlug'])) {
                        $aid = $this->managerRegistry->getRepository(Aid::class)
                            ->findOneBy(['slug' => $params['aidSlug']]);
                    }
                    $log->setAid($aid);
                    break;

                case 'applicationUrl':
                    $log = new LogAidApplicationUrlClick();
                    $log->setQuerystring($params['querystring']);
                    $log->setSource($this->getSiteFromHost($params['host']));
                    $source = $this->getLogAidSearchSourceInSession();
                    if ($source) {
                        $log->setSource($source);
                    }
                    $aid = null;
                    if (isset($params['aidSlug'])) {
                        $aid = $this->managerRegistry->getRepository(Aid::class)
                            ->findOneBy(['slug' => $params['aidSlug']]);
                    }
                    $log->setAid($aid);
                    break;

                case 'createDsFolder':
                    $log = new LogAidCreatedsFolder();
                    $log->setDsFolderUrl($params['dsFolderUrl'] ?? null);
                    $log->setDsFolderId($params['dsFolderId'] ?? null);
                    $log->setDsFolderNumber($params['dsFolderNumber'] ?? null);
                    $aid = null;
                    if (isset($params['aidSlug'])) {
                        $aid = $this->managerRegistry->getRepository(Aid::class)
                            ->findOneBy(['slug' => (string) $params['aidSlug']]);
                    }
                    $log->setAid($aid);
                    $origanization = null;
                    if (isset($params['organization'])) {
                        $origanization = $this->managerRegistry->getRepository(Organization::class)
                            ->find((int) $params['organization']);
                    }
                    $log->setOrganization($origanization);

                    $user = null;
                    if (isset($params['user'])) {
                        $user = $this->managerRegistry->getRepository(User::class)->find((int) $params['user']);
                    }
                    $log->setUser($user);
                    break;

                case self::AID_SEARCH:
                    $log = new LogAidSearchTemp();
                    $querystring = $params['querystring'] ?? null;
                    if ($querystring) {
                        // on nettoyage la querystring
                        $querystring = $this->cleanQueryString($querystring);
                    }
                    $log->setQuerystring($querystring);
                    $log->setResultsCount($params['resultsCount'] ?? null);
                    $log->setSource($this->getSiteFromHost($params['host'] ?? null));
                    if (isset($params['source'])) {
                        $log->setSource(substr($params['source'], 0, 255));
                    }
                    $log->setSearch(isset($params['search']) ? substr($params['search'], 0, 255) : null);
                    $log->setPerimeter($params['perimeter'] ?? null);
                    $log->setOrganization($params['organization'] ?? null);
                    $log->setUser($params['user'] ?? null);
                    if (isset($params['organizationTypes'])) {
                        foreach ($params['organizationTypes'] as $organizationType) {
                            $log->addOrganizationType($organizationType);
                        }
                    }
                    if (isset($params['backers'])) {
                        foreach ($params['backers'] as $backer) {
                            $log->addBacker($backer);
                        }
                    }
                    if (isset($params['categories'])) {
                        foreach ($params['categories'] as $category) {
                            $log->addCategory($category);
                        }
                    }
                    if (isset($params['programs'])) {
                        foreach ($params['programs'] as $program) {
                            $log->addProgram($program);
                        }
                    }
                    if (isset($params['themes'])) {
                        foreach ($params['themes'] as $theme) {
                            $log->addTheme($theme);
                        }
                    }
                    break;

                case self::AID_VIEW:
                    $log = new LogAidViewTemp();
                    $log->setQuerystring($params['querystring'] ?? null);
                    $log->setSource($this->getSiteFromHost($params['host'] ?? null));
                    $source = $this->getLogAidSearchSourceInSession();
                    if ($source) {
                        $log->setSource($source);
                    }
                    if (isset($params['source'])) {
                        $log->setSource(substr($params['source'], 0, 255));
                    }
                    $log->setAid($params['aid'] ?? null);
                    $log->setOrganization($params['organization'] ?? null);
                    $log->setUser($params['user'] ?? null);
                    break;
                case self::BACKER_VIEW:
                    $log = new LogBackerView();
                    $log->setSource($this->getSiteFromHost($params['host'] ?? null));
                    $log->setBacker($params['backer'] ?? null);
                    $log->setOrganization($params['organization'] ?? null);
                    $log->setUser($params['user'] ?? null);
                    break;

                case self::BLOG_POST_VIEW:
                    $log = new LogBlogPostView();
                    $log->setOrganization($params['organization'] ?? null);
                    $log->setUser($params['user'] ?? null);
                    $log->setBlogPost($params['blogPost'] ?? null);
                    break;

                case self::BLOG_PROMOTION_POST_CLICK:
                    $log = new LogBlogPromotionPostClick();
                    $log->setQuerystring($params['querystring'] ?? null);
                    $log->setSource($this->getSiteFromHost($params['host'] ?? null));
                    $blogPromotionPost = null;
                    if (isset($params['blogPromotionPostId'])) {
                        $blogPromotionPost = $this->managerRegistry->getRepository(BlogPromotionPost::class)
                            ->find((int) $params['blogPromotionPostId']);
                    }
                    $log->setBlogPromotionPost($blogPromotionPost);
                    break;

                case self::BLOG_PROMOTION_POST_DISPLAY:
                    $log = new LogBlogPromotionPostDisplay();
                    $log->setQuerystring($params['querystring'] ?? null);
                    $log->setSource($this->getSiteFromHost($params['host'] ?? null));
                    $blogPromotionPost = null;
                    if (isset($params['blogPromotionPostId'])) {
                        $blogPromotionPost = $this->managerRegistry->getRepository(BlogPromotionPost::class)
                            ->find((int) $params['blogPromotionPostId']);
                    }
                    $log->setBlogPromotionPost($blogPromotionPost);
                    break;

                case self::PROGRAM_VIEW:
                    $log = new LogProgramView();
                    $log->setSource($this->getSiteFromHost($params['host'] ?? null));
                    $log->setProgram($params['program'] ?? null);
                    $log->setOrganization($params['organization'] ?? null);
                    $log->setUser($params['user'] ?? null);
                    break;

                case self::PROJECT_VALIDATED_SEARCH:
                    $log = new LogProjectValidatedSearch();
                    $log->setSearch(isset($params['search']) ? substr($params['search'], 0, 255) : null);
                    $log->setQuerystring($params['querystring'] ?? null);
                    $log->setResultsCount($params['resultsCount'] ?? null);
                    $log->setOrganization($params['organization'] ?? null);
                    $log->setPerimeter($params['perimeter'] ?? null);
                    $log->setUser($params['user'] ?? null);
                    break;

                case self::PROJECT_PUBLIC_SEARCH:
                    $log = new LogPublicProjectSearch();
                    $log->setQuerystring($params['querystring'] ?? null);
                    $log->setResultsCount($params['resultsCount'] ?? null);
                    $log->setOrganization($params['organization'] ?? null);
                    $log->setPerimeter($params['perimeter'] ?? null);
                    $log->setUser($params['user'] ?? null);
                    if (isset($params['keywordSynonymlists']) && is_array($params['keywordSynonymlists'])) {
                        foreach ($params['keywordSynonymlists'] as $keywordSynonymlist) {
                            $log->addKeywordSynonymlist($keywordSynonymlist);
                        }
                    }
                    break;

                case self::PROJECT_PUBLIC_VIEW:
                    $log = new LogPublicProjectView();
                    $log->setProject($params['project'] ?? null);
                    $log->setOrganization($params['organization'] ?? null);
                    $log->setUser($params['user'] ?? null);
                    break;

                default:
                    // Code à exécuter si aucune des conditions précédentes n'est remplie
                    break;
            }

            if (isset($log)) {
                $this->managerRegistry->getManager()->persist($log);
                $this->managerRegistry->getManager()->flush();

                if ($type == self::AID_SEARCH && isset($querystring)) {
                    $this->setLogAidSearchTempIdInSession($log, $querystring);
                }
            }
        } catch (\Exception $exception) {
            $this->loggerInterface->error('Erreur log', [
                'exception' => $exception,
            ]);
        }
    }

    private function getLogAidSearchSourceInSession(): ?string
    {
        $logAidSearchTempId = $this->requestStack->getCurrentRequest()->getSession()->get(
            self::LAST_LOG_AID_SEARCH_ID,
            null
        );
        if (!$logAidSearchTempId) {
            return null;
        }

        $logAidSearchTemp = $this->managerRegistry->getRepository(LogAidSearchTemp::class)->find($logAidSearchTempId);
        if (!$logAidSearchTemp) {
            return null;
        }

        return $logAidSearchTemp->getSource();
    }

    private function setLogAidSearchTempIdInSession(LogAidSearchTemp $log, ?string $querystring = ''): void
    {
        if (!$querystring) {
            return;
        }

        // on regarde si il y a un id en session
        $lastLogAidSearchId = $this->requestStack->getCurrentRequest()->getSession()->get(
            self::LAST_LOG_AID_SEARCH_ID,
            null
        );
        if ($lastLogAidSearchId) {
            // on récupère le dernier log de recherche
            $lastLog = $this->managerRegistry->getRepository(LogAidSearchTemp::class)->find($lastLogAidSearchId);
            // on regarde si les paramètres de la requetes ont changés
            if (
                $lastLog instanceof LogAidSearchTemp
                && $this->removePageFromQuerystring($lastLog->getQuerystring())
                    != $this->removePageFromQuerystring($querystring)
            ) {
                // on stock l'id de la recherche dans la session pour le notififer dans l'ajout aux favoris
                $this->requestStack->getCurrentRequest()->getSession()->set(
                    self::LAST_LOG_AID_SEARCH_ID,
                    $log->getId()
                );
            }
        } else {
            // on stock l'id de la recherche dans la session pour le notififer dans l'ajout aux favoris
            $this->requestStack->getCurrentRequest()->getSession()->set(
                self::LAST_LOG_AID_SEARCH_ID,
                $log->getId()
            );
        }
    }

    private function cleanQueryString(string $querystring): string
    {
        // Convertir la querystring en tableau de paramètres
        parse_str($querystring, $params);

        // Supprimer les paramètres non désirés
        unset($params['_token']);
        unset($params['newIntegration']);

        // Reconstruire la querystring proprement
        return http_build_query($params);
    }

    private function removePageFromQuerystring(string $querystring): string
    {
        // Convertir la querystring en tableau de paramètres
        parse_str($querystring, $params);

        // Supprimer les paramètres non désirés
        unset($params['page']);

        // Reconstruire la querystring proprement
        return http_build_query($params);
    }

    public function getSiteFromHost(string $host): string
    {
        /**
         * Return the string bit that identify a site.
         * This can be the subdomain or a minisite slug.
         * aides-territoires.beta.gouv.fr --> aides-territoires
         * staging.aides-territoires.beta.gouv.fr --> staging
         * francemobilites.aides-territoires.beta.gouv.fr --> francemobilites
         * aides.francemobilites.fr --> francemobilites  # Using the mapping.
         */
        $mapDnsToMinisites = [
            ['aides-territoires.beta.gouv.fr', 'aides-territoires'],
            ['staging.aides-territoires.beta.gouv.fr', 'staging'],
            ['francemobilites.aides-territoires.beta.gouv.fr', 'francemobilites'],
            ['aides.francemobilites.fr', 'francemobilites'],
            ['centre-val-de-loire.aides-territoires.beta.gouv.fr', 'france-relance-cvl'],
            ['guyane.aides-territoires.beta.gouv.fr', 'france-relance-guyane'],
            ['martinique.aides-territoires.beta.gouv.fr', 'france-relance-martinique'],
            ['mayotte.aides-territoires.beta.gouv.fr', 'france-relance-mayotte'],
            ['reunion.aides-territoires.beta.gouv.fr', 'france-relance-reunion'],
            // Add more mappings here
        ];

        foreach ($mapDnsToMinisites as $mapping) {
            $minisite_host = $mapping[0];
            $minisite_slug = $mapping[1];
            // If we detect that a mapping is defined for the incoming
            // DNS host, then we get the minisite slug from that mapping.
            if (false !== strpos($host, $minisite_host)) {
                return $minisite_slug;
            }
        }

        if (false !== strpos($host, 'aides-territoires')) {
            return substr(explode('.', $host)[0], 0, 255);
        }

        return substr($host, 0, 255);
    }

    /**
     * @param array<string, mixed> $aidParams
     * @param integer $resultsCount
     * @param string|null $source
     * @param string|null $query
     * @return array<string, mixed>
     */
    public function getLogAidSearchParams(
        array $aidParams,
        int $resultsCount,
        ?string $source = null,
        ?string $query = null
    ): array {
        // le user actuellement connecté
        $user = $this->userService->getUserLogged();
        // la query
        $queryParsed = parse_url($this->requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;
        // Log recherche
        $logParams = [
            'organizationTypes' => (isset($aidParams['organizationType'])) ? [$aidParams['organizationType']] : null,
            'resultsCount' => $resultsCount,
            'host' => $this->requestStack->getCurrentRequest()->getHost(),
            'perimeter' => $aidParams['perimeterFrom'] ?? null,
            'search' => $aidParams['keyword'] ?? null,
            'organization' => ($user instanceof User && $user->getDefaultOrganization())
                ? $user->getDefaultOrganization()
                : null,
            'backers' => $aidParams['backers'] ?? null,
            'categories' => $aidParams['categories'] ?? null,
            'programs' => $aidParams['programs'] ?? null,
            'user' => $user ?? null,
        ];
        if ($source) {
            $logParams['source'] = $source;
        }
        $logParams['querystring'] = $query ?? $queryParsed;

        /** @var ArrayCollection<int, CategoryTheme> $themes */
        $themes = new ArrayCollection();
        if (isset($aidParams['categories']) && is_array($aidParams['categories'])) {
            foreach ($aidParams['categories'] as $category) {
                if (!$themes->contains($category->getCategoryTheme())) {
                    $themes->add($category->getCategoryTheme());
                }
            }
        }
        $logParams['themes'] = $themes->toArray();

        return $logParams;
    }
}
