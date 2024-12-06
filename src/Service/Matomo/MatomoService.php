<?php

namespace App\Service\Matomo;

use App\Entity\Aid\Aid;
use App\Service\Various\ParamService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MatomoService
{
    public const MATOMO_GET_PAGE_URLS_API_METHOD = "Actions.getPageUrls";
    public const MATOMO_GET_PAGE_URL_API_METHOD = "Actions.getPageUrl";
    public const MATOMO_GET_PAGE_TITLES_API_METHOD = "Actions.getPageTitles";
    public const MATOMO_GET_PAGE_TITLE_API_METHOD = "Actions.getPageTitle";
    public const GOAL_KEY = "_analytics_goal";

    public const REGEXP_AID_URL = 'aides/(.+)';
    public const REGEXP_AID_SLUG = 'aides/([^/]+)';

    public function __construct(
        private RequestStack $requestStack,
        private ParamService $paramService,
        private HttpClientInterface $httpClientInterface,
        private RouterInterface $routerInterface
    ) {
    }

    /**
     * Set an analytics goal to be tracked.
     *
     * @param int $goalId
     * @return void
     */
    public function trackGoal(int $goalId): void
    {
        $this->requestStack->getSession()->set(self::GOAL_KEY, $goalId);
    }

    /**
     * Returns the currently tracked goal id.
     *
     * Also, clears the session, so we only track a specific goal using
     * the js api once.
     *
     * @return int|null
     */
    public function getGoal(): ?int
    {
        try {
            $value = $this->requestStack->getSession()->get(self::GOAL_KEY);
            $this->requestStack->getSession()->set(self::GOAL_KEY, null);

            return $value;
        } catch (\Exception $e) {
            return null;
        }
    }

        /**
        *   Get stats of all Page Urls from Matomo.
        *   from_date_string & to_date_string must have YYYY-MM-DD format.
        *
        *   API Method examples:
        *   - 'Actions.getPageUrls' (views per page url)
        *   - 'Actions.getPageTitles' (views per page title)
        *   - 'Actions.getSiteSearchKeywords' (keywords searched in the the application)
        *
        *   Custom segments examples:
        *   https://developer.matomo.org/api-reference/reporting-api-segmentation
        *   - 'pageUrl=@actioncoeurdeville.aides-territoires.beta.gouv.fr' (url must contain string)
        *   - 'pageTitle==Aides-territoires | Recherche avancée'
        *
        *   Usage example:
        *   get_matomo_stats_from_page_title(
        *      'Actions.getPageUrls',
        *      from_date_string='2020-01-01',
        *      to_date_string='2020-12-31'
        *  )
        *
        * @param string $apiMethod
        * @param string|null $customSegment
        * @param string $fromDateString
        * @param string|null $toDateString
        * @param string|null $period
        * @param array<string, mixed>|null $options
        * @return mixed
    */
    public function getMatomoStats(
        string $apiMethod,
        ?string $customSegment = "",
        string $fromDateString = "2023-01-01",
        string $toDateString = null,
        ?string $period = 'range',
        ?array $options = null
    ): mixed {
        try {
            $date = $fromDateString;
            if ($toDateString) {
                $date .= ',' . $toDateString;
            }

            $params = [
                "idSite" => $this->paramService->get('matomo_site_id'),
                "module" => "API",
                "method" => $apiMethod,
                "period" => $period,
                "date" => $date,
                "flat" => 1,
                "filter_limit" => -1,
                "format" => "json",
                "segment" => $customSegment,
            ];

            if (is_array($options)) {
                $params = array_merge($params, $options);
            }
            $response = $this->httpClientInterface->request(
                'GET',
                $this->paramService->get('matomo_endpoint'),
                [
                    'query' => $params
                ]
            );

            return json_decode($response->getContent());
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAidUniqueVisitors(Aid $aid, \DateTime $dateStart, \DateTime $dateEnd): int
    {
        $pageUrl = 'https://aides-territoires.beta.gouv.fr/aides/' . $aid->getSlug();
        $endDate = new \DateTime('yesterday');
        if ($dateEnd > $endDate) {
            $dateEnd = clone $endDate;
        }

        $results = $this->getMatomoStats(
            'VisitsSummary.getUniqueVisitors',
            'pageUrl==' . $pageUrl,
            $dateStart->format('Y-m-d'),
            $dateEnd->format('Y-m-d'),
            'range'
        );

        return $results[0]->nb_uniq_visitors ?? 0;
    }

    /**
     * Récupère les stats pour plusieurs périodes
     */
    public function getAidsStats(): array
    {
        $periods = [
            '30_days' => ['from' => '-30 days', 'to' => 'today'],
            'one_year' => ['from' => '-1 year', 'to' => 'today']
        ];

        $minDate = new \DateTime('2023-12-07');
        $endDate = new \DateTime('yesterday');
        $allStats = [];
        
        foreach ($periods as $period => $dates) {
            $fromDate = new \DateTime($dates['from']);
            $toDate = new \DateTime($dates['to']);

            // Vérification de la date minimum avec DateTime
            if ($fromDate < $minDate) {
                $fromDate = clone $minDate;
            }

            if ($toDate > $endDate) {
                $toDate = clone $endDate;
            }

            $stats = $this->getMatomoStats(
                apiMethod: self::MATOMO_GET_PAGE_URLS_API_METHOD,
                fromDateString: $fromDate->format('Y-m-d'),
                toDateString: $toDate->format('Y-m-d'),
                options: [
                    'flat' => 1,
                    'filter_column' => 'label',
                    'filter_pattern' => 'aides'
                ],
            );
            
            $allStats[$period] = $this->processStats($stats);
        }

        return $allStats;
    }

    private function processStats($stats): array
    {
        $processed = [];
        foreach ($stats as $stat) {
            if (preg_match('~' . self::REGEXP_AID_SLUG . '~', $stat->label, $matches)) {
                $slug = $matches[1];
                $processed[$slug] = [
                    'views' => $stat->nb_visits ?? 0,
                    'hits' => $stat->nb_hits ?? 0
                ];
            }
        }
        return $processed;
    }
}
