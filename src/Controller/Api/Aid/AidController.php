<?php

namespace App\Controller\Api\Aid;

use App\Controller\Api\ApiController;
use App\Entity\Aid\Aid;
use App\Entity\Category\CategoryTheme;
use App\Entity\Log\LogAidSearch;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Repository\Aid\AidRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Log\LogService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AsController]
class AidController extends ApiController
{
    #[Route('/api/aids/', name: 'api_aid_aids', priority: 5)]
    public function index(
        AidService $aidService,
        AidSearchFormService $aidSearchFormService,
        LogService $logService,
        RequestStack $requestStack,
        UserService $userService
    ): JsonResponse {
        // paramètres de recherche
        $aidSearchClass = $aidSearchFormService->getAidSearchClass(null, [
            'dontUseUserOrganizationType' => true,
            'dontUseUserPerimeter' => true
        ]);

        // parametres pour requetes aides
        $aidParams = [
            'showInSearch' => true,
        ];

        // on fusionne les paramètres de recherche avec les paramètres de requête
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));

        // clé pour le cache
        $cacheKey = 'api_aids_' . hash('xxh128', serialize([
            'params' => $aidParams,
            'page' => $this->getPage(),
            'date' => (new \DateTime())->format('Y-m-d'),
        ]));

        // on recupere les données du cache
        $apiDatas = $this->cache->get(
            $cacheKey,
            function () use ($aidParams, $aidService) {
                // lance la recherche
                $results = $aidService->searchAidsV3($aidParams);

                // on compte le nombre de résultats
                $count = count($results);

                // on extrait les résultats pour la pagination
                $results = array_slice($results, ($this->getPage() - 1) * $this->getItemsPerPage(), $this->getItemsPerPage());
                
                // retransforme les ids à afficher en aide
                $results = $aidService->hydrateLightAids($results, $aidParams);

                // spécifique
                return [
                    'count' => $count,
                    'resultsSpe' => $this->getResultsSpe($results, $aidService)
                ];
            }
        );

        // le retour
        $data = [
            'count' => $apiDatas['count'],
            'previous' => $this->getPrevious(),
            'next' => $this->getNext($apiDatas['count']),
            'results' => $apiDatas['resultsSpe']
        ];

        // Log recherche
        $query = $aidSearchFormService->convertAidSearchClassToQueryString($aidSearchClass);

        $user = $userService->getUserLogged();
        $logParams = [
            'organizationTypes' => (isset($aidParams['organizationType'])) ? [$aidParams['organizationType']] : null,
            'querystring' => $query,
            'resultsCount' => $apiDatas['count'],
            'host' => $requestStack->getCurrentRequest()->getHost(),
            'source' => LogAidSearch::SOURCE_API,
            'perimeter' => $aidParams['perimeterFrom'] ?? null,
            'search' => $aidParams['keyword'] ?? null,
            'organization' => ($user instanceof User && $user->getDefaultOrganization())
                ? $user->getDefaultOrganization() : null,
            'backers' => $aidParams['backers'] ?? null,
            'categories' => $aidParams['categories'] ?? null,
            'programs' => $aidParams['programs'] ?? null,
            'projectReference' => $aidParams['projectReference'] ?? null,
            'user' => $user ?? null
        ];
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
        $logService->log(
            type: LogService::AID_SEARCH,
            params: $logParams,
        );

        // la réponse
        $response =  new JsonResponse($data, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);

        return $response;
    }

    #[Route('/api/aids/all/', name: 'api_aid_all', priority: 5)]
    public function all(
        AidService $aidService
    ): JsonResponse {
        $params = [
            'showInSearch' => true,
            'orderBy' => ['sort' => 'a.id', 'order' => 'DESC']
        ];
        // requete pour compter sans la pagination
        $results = $aidService->searchAidsV3($params);
        $count = count($results);

        // requete pour les résultats avec la pagination
        $results = array_slice($results, ($this->getPage() - 1) * $this->getItemsPerPage(), $this->getItemsPerPage());
        $results = $aidService->hydrateLightAids($results, $params);
        // spécifique
        $resultsSpe = $this->getResultsSpe($results, $aidService);

        // le retour
        $data = [
            'count' => $count,
            'previous' => $this->getPrevious(),
            'next' => $this->getNext($count),
            'results' => $resultsSpe
        ];

        // la réponse
        $response =  new JsonResponse($data, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }

    #[Route('/api/aids/by-id/{id}/', name: 'api_aid_by_id', priority: 4)]
    public function byId(
        mixed $id,
        AidRepository $aidRepository,
        AidService $aidService,
        LogService $logService,
        RequestStack $requestStack,
        UserService $userService
    ): JsonResponse {
        $params = [];
        // $id peu être un slug ou un id
        if (is_numeric($id)) {
            $params['id'] = (int) $id;
        } else {
            $params['slug'] = $id;
        }
        $codeStatus = 200;
        $aid = $aidRepository->findOneCustom($params);
        if ($aid instanceof Aid) {
            $results = [$aid];

            // spécifique
            $resultsSpe = $this->getResultsSpe($results, $aidService);
            $data = $resultsSpe[0];
        } else {
            $data = [
                'type' => 'about:blank',
                'title' => 'Not Found',
                'status' => 404,
                "detail" => "Cette aide n'existe pas"
            ];
            $codeStatus = 404;
        }

        // log
        $user = $userService->getUserLogged();
        $logService->log(
            type: LogService::AID_VIEW,
            params: [
                'querystring' => parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null,
                'host' => $requestStack->getCurrentRequest()->getHost(),
                'aid' => $aid,
                'organization' => ($user instanceof User && $user->getDefaultOrganization())
                    ? $user->getDefaultOrganization() : null,
                'user' => ($user instanceof User) ? $user : null,
                'source' => LogAidSearch::SOURCE_API,
            ]
        );

        // la réponse
        $response =  new JsonResponse($data, $codeStatus, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }

    #[Route('/api/aids/{slug}/', name: 'api_aid_by_slug', priority: 4)]
    public function bySlug(
        string $slug,
        AidRepository $aidRepository,
        AidService $aidService,
        LogService $logService,
        RequestStack $requestStack,
        UserService $userService
    ): JsonResponse {
        $codeStatus = 200;
        $params = [];
        $params['slug'] = $slug;
        $aid = $aidRepository->findOneCustom($params);
        if ($aid instanceof Aid) {
            $results = [$aid];

            // spécifique
            $resultsSpe = $this->getResultsSpe($results, $aidService);
            $data = $resultsSpe[0];
        } else {
            $data = [
                'type' => 'about:blank',
                'title' => 'Not Found',
                'status' => 404,
                "detail" => "Cette aide n'existe pas"
            ];
            $codeStatus = 404;
        }

        // log
        $user = $userService->getUserLogged();
        $logService->log(
            type: LogService::AID_VIEW,
            params: [
                'querystring' => parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null,
                'host' => $requestStack->getCurrentRequest()->getHost(),
                'aid' => $aid,
                'organization' => ($user instanceof User && $user->getDefaultOrganization())
                    ? $user->getDefaultOrganization() : null,
                'user' => ($user instanceof User) ? $user : null,
                'source' => LogAidSearch::SOURCE_API,
            ]
        );

        // la réponse
        $response =  new JsonResponse($data, $codeStatus, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }


    /**
     * Formatage du retour
     *
     * @param array<int, Aid> $results
     * @param AidService $aidService
     * @return array<int, array<string, mixed>>
     */
    private function getResultsSpe(array $results, AidService $aidService): array
    {
        $resultsSpe = [];

        /** @var Aid $result */
        foreach ($results as $result) {
            $financers = [];
            $financersFull = [];
            foreach ($result->getAidFinancers() as $aidFinancer) {
                if (!$aidFinancer->getBacker()) {
                    continue;
                }
                    $financers[] = $aidFinancer->getBacker()->getName();
                    $financersFull[] = [
                        'id' => $aidFinancer->getBacker()->getId(),
                        'name' => $aidFinancer->getBacker()->getName(),
                        'logo' => $aidFinancer->getBacker()->getLogo()
                            ? $this->paramService->get('cloud_image_url') . $aidFinancer->getBacker()->getLogo()
                            : null
                    ];
            }

            $instructors = [];
            $instructorsFull = [];
            foreach ($result->getAidInstructors() as $aidInstructor) {
                if (!$aidInstructor->getBacker()) {
                    continue;
                }

                $instructors[] = $aidInstructor->getBacker()->getName();
                $instructorsFull[] = [
                    'id' => $aidInstructor->getBacker()->getId(),
                    'name' => $aidInstructor->getBacker()->getName(),
                    'logo' => $aidInstructor->getBacker()->getLogo()
                        ? $this->paramService->get('cloud_image_url') . $aidInstructor->getBacker()->getLogo()
                        : null
                ];
            }
            
            $programs = [];
            foreach ($result->getPrograms() as $program) {
                $programs[] = $program->getName();
            }
            $steps = [];
            foreach ($result->getAidSteps() as $step) {
                $steps[] = $step->getName();
            }
            $categories = [];
            foreach ($result->getCategories() as $category) {
                $fullname = '';
                if ($category->getCategoryTheme()) {
                    $fullname .= $category->getCategoryTheme()->getName() . ' / ';
                }
                $fullname .= $category->getName();
                $categories[] = $fullname;
            }
            $audiences = [];
            foreach ($result->getAidAudiences() as $aidAudience) {
                $audiences[] = $aidAudience->getName();
            }
            $types = [];
            $typesFull = [];
            foreach ($result->getAidTypes() as $aidType) {
                if (!$aidType->getAidTypeGroup()) {
                    continue;
                }
                $types[] = $aidType->getName();
                $typesFull[] = [
                    'id' => $aidType->getId(),
                    'name' => $aidType->getName(),
                    'group' => $aidType->getAidTypeGroup()
                        ? [
                            'id' => $aidType->getAidTypeGroup()->getId(),
                            'name' => $aidType->getAidTypeGroup()->getName()
                        ]
                        : null
                ];
            }
            

            $destinations = [];
            foreach ($result->getAidDestinations() as $aidDestination) {
                $destinations[] = $aidDestination->getName();
            }

            $projectReferences = [];
            foreach ($result->getProjectReferences() as $projectReference) {
                $projectReferences[] = $projectReference->getName();
            }
            $resultsSpe[] = [
                'id' => $result->getId(),
                'slug' => $result->getSlug(),
                'url' => $aidService->getUrl($result, UrlGeneratorInterface::ABSOLUTE_PATH),
                'name' => $result->getName(),
                'name_initial' => $result->getNameInitial(),
                'short_title' => $result->getShortTitle(),
                'financers' => $financers,
                'financers_full' => $financersFull,
                'instructors' => $instructors,
                'instructors_full' => $instructorsFull,
                'programs' => $programs,
                'description' => $result->getDescription(),
                'eligibility' => $result->getEligibility(),
                'perimeter' => $result->getPerimeter() ? $result->getPerimeter()->getName() : null,
                'perimeter_scale' => (
                    $result->getPerimeter()
                    && $result->getPerimeter()->getScale()
                    && isset(Perimeter::SCALES_FOR_SEARCH[$result->getPerimeter()->getScale()])
                ) ? Perimeter::SCALES_FOR_SEARCH[$result->getPerimeter()->getScale()]['name'] : null,
                'mobilization_steps' => $steps,
                'origin_url' => $result->getOriginUrl(),
                'categories' => $categories,
                'is_call_for_project' => $result->isIsCallForProject(),
                'application_url' => $result->getApplicationUrl(),
                'targeted_audiences' => $audiences,
                'aid_types' => $types,
                'aid_types_full' => $typesFull,
                'is_charged' => $result->isIsCharged(),
                'destinations' => $destinations,
                'start_date' => $result->getDateStart()
                    ? $result->getDateStart()->format('Y-m-d') : null,
                'predeposit_date' => $result->getDatePredeposit()
                    ? $result->getDatePredeposit()->format('Y-m-d') : null,
                'submission_deadline' => $result->getDateSubmissionDeadline()
                    ? $result->getDateSubmissionDeadline()->format('Y-m-d') : null,
                'subvention_rate_lower_bound' => $result->getSubventionRateMin(),
                'subvention_rate_upper_bound' => $result->getSubventionRateMax(),
                'subvention_comment' => $result->getSubventionComment(),
                'loan_amount' => $result->getLoanAmount(),
                'recoverable_advance_amount' => $result->getRecoverableAdvanceAmount(),
                'contact' => $result->getContact(),
                'recurrence' => $result->getAidRecurrence() ? $result->getAidRecurrence()->getName() : null,
                'project_examples' => $result->getProjectExamples(),
                'import_data_url' => $result->getImportDataUrl(),
                'import_data_mention' => $result->getImportDataMention(),
                'import_share_licence' => $result->getImportShareLicence(),
                'date_created' => $result->getTimeCreate()->format(\DateTime::ATOM),
                'date_updated' => $result->getTimeUpdate() ? $result->getTimeUpdate()->format(\DateTime::ATOM) : null,
                'project_references' => $projectReferences,
                'european_aid' => $result->getEuropeanAid(),
                'is_live' => $result->isLive()
            ];
        }

        return $resultsSpe;
    }

        /**
     * Formatage du retour
     *
     * @param array<int, Aid> $results
     * @param AidService $aidService
     * @return array<int, array<string, mixed>>
     */
    private function getResultsSpeCache(array $aidParams, array $results, AidService $aidService, int $page): array
    {
        $cacheKey = 'results_spe_' . hash('xxh128', serialize([
            'params' => $aidParams,
            'page' => $page,
            'date' => (new \DateTime())->format('Y-m-d'),
        ]));

    
        // $cacheKey = 'test';

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($results, $aidService) {
            $cloudUrl = $this->paramService->get('cloud_image_url');
            $finalResults = [];
            /** @var Aid $result */
            foreach ($results as $result) {
                $financers = [];
                $financersFull = [];
                foreach ($result->getAidFinancers() as $aidFinancer) {
                    if (!$aidFinancer->getBacker()) {
                        continue;
                    }
                        $financers[] = $aidFinancer->getBacker()->getName();
                        $financersFull[] = [
                            'id' => $aidFinancer->getBacker()->getId(),
                            'name' => $aidFinancer->getBacker()->getName(),
                            'logo' => $aidFinancer->getBacker()->getLogo()
                                ? $cloudUrl . $aidFinancer->getBacker()->getLogo()
                                : null
                        ];
                }
    
                $instructors = [];
                $instructorsFull = [];
                foreach ($result->getAidInstructors() as $aidInstructor) {
                    if (!$aidInstructor->getBacker()) {
                        continue;
                    }
    
                    $instructors[] = $aidInstructor->getBacker()->getName();
                    $instructorsFull[] = [
                        'id' => $aidInstructor->getBacker()->getId(),
                        'name' => $aidInstructor->getBacker()->getName(),
                        'logo' => $aidInstructor->getBacker()->getLogo()
                            ? $cloudUrl . $aidInstructor->getBacker()->getLogo()
                            : null
                    ];
                }
                
                $programs = [];
                foreach ($result->getPrograms() as $program) {
                    $programs[] = $program->getName();
                }
                $steps = [];
                foreach ($result->getAidSteps() as $step) {
                    $steps[] = $step->getName();
                }
                $categories = [];
                foreach ($result->getCategories() as $category) {
                    $fullname = '';
                    if ($category->getCategoryTheme()) {
                        $fullname .= $category->getCategoryTheme()->getName() . ' / ';
                    }
                    $fullname .= $category->getName();
                    $categories[] = $fullname;
                }
                $audiences = [];
                foreach ($result->getAidAudiences() as $aidAudience) {
                    $audiences[] = $aidAudience->getName();
                }
                $types = [];
                $typesFull = [];
                foreach ($result->getAidTypes() as $aidType) {
                    if (!$aidType->getAidTypeGroup()) {
                        continue;
                    }
                    $types[] = $aidType->getName();
                    $typesFull[] = [
                        'id' => $aidType->getId(),
                        'name' => $aidType->getName(),
                        'group' => $aidType->getAidTypeGroup()
                            ? [
                                'id' => $aidType->getAidTypeGroup()->getId(),
                                'name' => $aidType->getAidTypeGroup()->getName()
                            ]
                            : null
                    ];
                }
                
    
                $destinations = [];
                foreach ($result->getAidDestinations() as $aidDestination) {
                    $destinations[] = $aidDestination->getName();
                }
    
                $projectReferences = [];
                foreach ($result->getProjectReferences() as $projectReference) {
                    $projectReferences[] = $projectReference->getName();
                }
                $finalResults[] = [
                    'id' => $result->getId(),
                    'slug' => $result->getSlug(),
                    'url' => $aidService->getUrl($result, UrlGeneratorInterface::ABSOLUTE_PATH),
                    'name' => $result->getName(),
                    'name_initial' => $result->getNameInitial(),
                    'short_title' => $result->getShortTitle(),
                    'financers' => $financers,
                    'financers_full' => $financersFull,
                    'instructors' => $instructors,
                    'instructors_full' => $instructorsFull,
                    'programs' => $programs,
                    'description' => $result->getDescription(),
                    'eligibility' => $result->getEligibility(),
                    'perimeter' => $result->getPerimeter() ? $result->getPerimeter()->getName() : null,
                    'perimeter_scale' => (
                        $result->getPerimeter()
                        && $result->getPerimeter()->getScale()
                        && isset(Perimeter::SCALES_FOR_SEARCH[$result->getPerimeter()->getScale()])
                    ) ? Perimeter::SCALES_FOR_SEARCH[$result->getPerimeter()->getScale()]['name'] : null,
                    'mobilization_steps' => $steps,
                    'origin_url' => $result->getOriginUrl(),
                    'categories' => $categories,
                    'is_call_for_project' => $result->isIsCallForProject(),
                    'application_url' => $result->getApplicationUrl(),
                    'targeted_audiences' => $audiences,
                    'aid_types' => $types,
                    'aid_types_full' => $typesFull,
                    'is_charged' => $result->isIsCharged(),
                    'destinations' => $destinations,
                    'start_date' => $result->getDateStart()
                        ? $result->getDateStart()->format('Y-m-d') : null,
                    'predeposit_date' => $result->getDatePredeposit()
                        ? $result->getDatePredeposit()->format('Y-m-d') : null,
                    'submission_deadline' => $result->getDateSubmissionDeadline()
                        ? $result->getDateSubmissionDeadline()->format('Y-m-d') : null,
                    'subvention_rate_lower_bound' => $result->getSubventionRateMin(),
                    'subvention_rate_upper_bound' => $result->getSubventionRateMax(),
                    'subvention_comment' => $result->getSubventionComment(),
                    'loan_amount' => $result->getLoanAmount(),
                    'recoverable_advance_amount' => $result->getRecoverableAdvanceAmount(),
                    'contact' => $result->getContact(),
                    'recurrence' => $result->getAidRecurrence() ? $result->getAidRecurrence()->getName() : null,
                    'project_examples' => $result->getProjectExamples(),
                    'import_data_url' => $result->getImportDataUrl(),
                    'import_data_mention' => $result->getImportDataMention(),
                    'import_share_licence' => $result->getImportShareLicence(),
                    'date_created' => $result->getTimeCreate()->format(\DateTime::ATOM),
                    'date_updated' => $result->getTimeUpdate() ? $result->getTimeUpdate()->format(\DateTime::ATOM) : null,
                    'project_references' => $projectReferences,
                    'european_aid' => $result->getEuropeanAid(),
                    'is_live' => $result->isLive()
                ];
            }

            // dd('pas de cache');
            $item->expiresAfter(86400); // 24h
            $item->tag(['aids_api', 'resultsSpe']);

            return $finalResults;
        });
    }
}
