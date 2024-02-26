<?php

namespace App\Service\Log;

use App\Entity\Aid\Aid;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\Log\LogAccountRegisterFromNextPageWarningClickEvent;
use App\Entity\Log\LogAidApplicationUrlClick;
use App\Entity\Log\LogAidCreatedsFolder;
use App\Entity\Log\LogAidOriginUrlClick;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogAidView;
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
use Doctrine\Persistence\ManagerRegistry;

class LogService
{
    const AID_SEARCH = 'aidSearch';
    const AID_VIEW = 'aidView';
    const BACKER_VIEW = 'backerView';
    const BLOG_POST_VIEW = 'blogPostView';
    const BLOG_PROMOTION_POST_CLICK = 'blogPromotionPostClick';
    const BLOG_PROMOTION_POST_DISPLAY = 'blogPromotionPostDisplay';
    const PROGRAM_VIEW = 'programView';
    const PROJECT_VALIDATED_SEARCH = 'projectValidatedSearch';
    const PROJECT_PUBLIC_SEARCH = 'projectPublicSearch';
    const PROJECT_PUBLIC_VIEW = 'projectPublicView';

    public function __construct(
        private ManagerRegistry $managerRegistry
    )
    {
    }

    public function log(
        ?string $type,
        ?array $params,
    ): void
    {
        try {
            switch ($type) {
                case 'register-from-next-page-warning':
                    $querystring = '';
                    if (is_array($params)) {
                        foreach ($params as $key => $param) {
                            if ($key == '_token') { // pas besoin de stocker le tocken
                                continue;
                            }
                            $querystring .= $key.'='.$param . '&';
                        }
                        $querystring = substr($querystring, 0, -1); // on enlève le dernier & (qui est en trop)
                    }
                    if (trim($querystring) == '') {
                        $querystring = null;
                    }
                    $log = new LogAccountRegisterFromNextPageWarningClickEvent();
                    $log->setQuerystring($querystring);
                    
                    break;
    
                case 'originUrl':
                    $log = new LogAidOriginUrlClick();
                    $log->setQuerystring($params['querystring']);
                    $log->setSource($this->getSiteFromHost($params['host']));
                    $aid = null;
                    if (isset($params['aidSlug'])) {
                        $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(['slug' => $params['aidSlug']]);
                    }
                    $log->setAid($aid);
                    break;
    
                    case 'applicationUrl':
                        $log = new LogAidApplicationUrlClick();
                        $log->setQuerystring($params['querystring']);
                        $log->setSource($this->getSiteFromHost($params['host']));
                        $aid = null;
                        if (isset($params['aidSlug'])) {
                            $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(['slug' => $params['aidSlug']]);
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
                            $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(['slug' => (string) $params['aidSlug']]);
                        }
                        $log->setAid($aid);
                        $origanization = null;
                        if (isset($params['organization'])) {
                            $origanization = $this->managerRegistry->getRepository(Organization::class)->find((int) $params['organization']);
                        }
                        $log->setOrganization($origanization);

                        $user = null;
                        if (isset($params['user'])) {
                            $user = $this->managerRegistry->getRepository(User::class)->find((int) $params['user']);
                        }
                        $log->setUser($user);
                        break;
    
                    case self::AID_SEARCH:
                        $log = new LogAidSearch();
                        $log->setQuerystring($params['querystring'] ?? null);
                        $log->setResultsCount($params['resultsCount'] ?? null);
                        $log->setSource($this->getSiteFromHost($params['host'] ?? null));
                        if (isset($params['source'])) {
                            $log->setSource($params['source']);
                        }
                        $log->setSearch($params['search'] ?? null);
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
                        $log = new LogAidView();
                        $log->setQuerystring($params['querystring'] ?? null);
                        $log->setSource($this->getSiteFromHost($params['host'] ?? null));
                        if (isset($params['source'])) {
                            $log->setSource($params['source']);
                        }
                        $log->setAid($params['aid'] ?? null);
                        $log->setOrganization($params['organization'] ?? null);
                        $log->setUser($params['user'] ?? null);
                        if (isset($params['organizationTypes'])) {
                            foreach ($params['organizationTypes'] as $organizationType) {
                                $log->addOrganizationType($organizationType);
                            }
                        }
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
                            $blogPromotionPost = $this->managerRegistry->getRepository(BlogPromotionPost::class)->find((int) $params['blogPromotionPostId']);
                        }
                        $log->setBlogPromotionPost($blogPromotionPost);
                        break;

                        case self::BLOG_PROMOTION_POST_DISPLAY:
                            $log = new LogBlogPromotionPostDisplay();
                            $log->setQuerystring($params['querystring'] ?? null);
                            $log->setSource($this->getSiteFromHost($params['host'] ?? null));
                            $blogPromotionPost = null;
                            if (isset($params['blogPromotionPostId'])) {
                                $blogPromotionPost = $this->managerRegistry->getRepository(BlogPromotionPost::class)->find((int) $params['blogPromotionPostId']);
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
                            $log->setSearch($params['search'] ?? null);
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
    
            $this->managerRegistry->getManager()->persist($log);
            $this->managerRegistry->getManager()->flush();
        } catch (\Exception $exception) {
        }
    }

    public function getSiteFromHost($host)
    {
        /**
         * Return the string bit that identify a site.
         * This can be the subdomain or a minisite slug.
         * aides-territoires.beta.gouv.fr --> aides-territoires
         * staging.aides-territoires.beta.gouv.fr --> staging
         * francemobilites.aides-territoires.beta.gouv.fr --> francemobilites
         * aides.francemobilites.fr --> francemobilites  # Using the mapping
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
            if (strpos($host, $minisite_host) !== false) {
                return $minisite_slug;
            }
        }

        if (strpos($host, "aides-territoires") !== false) {
            return explode(".", $host)[0];
        }

        return $host;
    }
}