<?php

namespace App\Service\Matomo;

use App\Service\Various\ParamService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MatomoService
{
    const MATOMO_GET_PAGE_URLS_API_METHOD = "Actions.getPageUrls";
    const MATOMO_GET_PAGE_TITLES_API_METHOD = "Actions.getPageTitles";
    const MATOMO_GET_PAGE_TITLE_API_METHOD = "Actions.getPageTitle";
    const GOAL_KEY = "_analytics_goal";

    public function __construct(
        protected RequestStack $requestStack,
        protected ParamService $paramService,
        protected HttpClientInterface $httpClientInterface
    )
    {
    }

    /**
     * Set an analytics goal to be tracked.
     *
     * @param [type] $goalId
     * @return void
     */
    public function trackGoal($goalId): void
    {
        $this->requestStack->getSession()->set(self::GOAL_KEY, $goalId);
    }

    /**
     * Returns the currently tracked goal id.
     *
     * Also, clears the session, so we only track a specific goal using
     * the js api once.
     *
     * @return string
     */
    public function getGoal(): ?string
    {
        $value = $this->requestStack->getSession()->get(self::GOAL_KEY);
        $this->requestStack->getSession()->set(self::GOAL_KEY, null);

        return $value;
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
     *   get_matomo_stats_from_page_title('Actions.getPageUrls', from_date_string='2020-01-01', to_date_string='2020-12-31') 
     *
     * @param [type] $api_method
     * @param string $custom_segment
     * @param string $from_date_string
     * @param [type] $to_date_string
     * @return array|null
     */
    public function getMatomoStats(
        $apiMethod,
        ?string $customSegment="",
        string $fromDateString="2023-01-01",
        string $toDateString=null,
        ?string $period= 'range'
    ): mixed
    {
        try {
            $date = $fromDateString;
            if ($toDateString) {
                $date .= ','.$toDateString;
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

            $response = $this->httpClientInterface->request(
                'GET',
                $this->paramService->get('matomo_endpoint'),
                [
                    'query' => $params
                ]
            );
// dd(json_decode($response->getContent()));
            return json_decode($response->getContent());
        } catch (\Exception $e) {
            return null;
        }
    }    
}