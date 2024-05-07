<?php

namespace App\Controller\Api\Aid;

use App\Controller\Api\ApiController;
use App\Entity\Aid\Aid;
use App\Entity\Log\LogAidSearch;
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

#[AsController]
class AidController extends ApiController
{
    #[Route('/api/aids/', name: 'api_aid_aids', priority: 5)]
    public function index(
        AidRepository $aidRepository,
        AidService $aidService,
        AidSearchFormService $aidSearchFormService,
        LogService $logService,
        RequestStack $requestStack,
        UserService $userService
    ): JsonResponse
    {
        $aidSearchClass = $aidSearchFormService->getAidSearchClass();
        // parametres pour requetes aides
        $aidParams = [
            'showInSearch' => true,
        ];
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));

        // requete pour compter sans la pagination
        $countParams = $aidParams;
        $count = $aidRepository->countAfterSelect($countParams);

        // requete pour les résultats avec la pagination
        $aidParams['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $aidParams['maxResults'] = $this->getItemsPerPage();
        
        $results = $aidService->searchAids($aidParams);

        // spécifique
        $resultsSpe = $this->getResultsSpe($results, $aidService);
        

        // le retour
        $data = [
            'count' => $count,
            'previous' => $this->getPrevious(),
            'next' => $this->getNext($count),
            'results' => $resultsSpe
        ];


        // Log recherche
        $query = $aidSearchFormService->convertAidSearchClassToQueryString($aidSearchClass);

        $user = $userService->getUserLogged();
        $logParams = [
            'organizationTypes' => (isset($aidParams['organizationType'])) ? [$aidParams['organizationType']] : null,
            'querystring' => $query ?? null,
            'resultsCount' => $count,
            'host' => $requestStack->getCurrentRequest()->getHost(),
            'source' => LogAidSearch::SOURCE_API,
            'perimeter' => $aidParams['perimeter'] ?? null,
            'search' => $aidParams['keyword'] ?? null,
            'organization' => ($user instanceof User && $user->getDefaultOrganization()) ? $user->getDefaultOrganization() : null,
            'backers' => $aidParams['backers'] ?? null,
            'categories' => $aidParams['categories'] ?? null,
            'programs' => $aidParams['programs'] ?? null,
            'projectReference' => $aidParams['projectReference'] ?? null
        ];
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
        AidRepository $aidRepository,
        AidService $aidService
    ): JsonResponse
    {
        $params = [];
        $params['status'] = Aid::STATUS_PUBLISHED;
        $params['orderBy'] = ['sort' => 'a.id', 'order' => 'DESC'];

        // requete pour compter sans la pagination
        $count = $aidRepository->countCustom($params);
        
        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();
    
        $results = $aidRepository->findCustom($params);
        
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
    #[Route('/api/aids/{slug}/', name: 'api_aid_by_slug', priority: 4)]
    public function bySlug(
        $slug,
        AidRepository $aidRepository,
        AidService $aidService,
        LogService $logService,
        RequestStack $requestStack,
        UserService $userService
    ): JsonResponse
    {
        $params = [];
        $params['showInSearch'] = true;
        $params['slug'] = $slug;
        $aid = $aidRepository->findOneCustom($params);
        if ($aid instanceof Aid) {
            $results = [$aid];
        
            // spécifique
            $resultsSpe = $this->getResultsSpe($results, $aidService);
            $data = $resultsSpe[0];
        } else {
            $data = [
                "detail" => "Pas trouvé."
            ];
        }

        // log
        $user = $userService->getUserLogged();
        $logService->log(
            type: LogService::AID_VIEW,
            params: [
                'querystring' => parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null,
                'host' => $requestStack->getCurrentRequest()->getHost(),
                'aid' => $aid,
                'organization' => ($user instanceof User && $user->getDefaultOrganization()) ? $user->getDefaultOrganization() : null,
                'user' => ($user instanceof User) ? $user : null,
                'source' => LogAidSearch::SOURCE_API,
            ]
        );
        
        // la réponse
        $response =  new JsonResponse($data, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }


    private function getResultsSpe(array $results, AidService $aidService): array
    {
        $resultsSpe = [];

        /** @var Aid $result */
        foreach ($results as $result) {
            $financers = [];
            foreach ($result->getAidFinancers() as $aidFinancer) {
                if ($aidFinancer->getBacker()) {
                    $financers[] = $aidFinancer->getBacker()->getName();
                }
            }
            $instructors = [];
            foreach ($result->getAidInstructors() as $aidInstructor) {
                if ($aidInstructor->getBacker()) {
                    $instructors[] = $aidInstructor->getBacker()->getName();
                }
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
                    $fullname .= $category->getCategoryTheme()->getName().' / ';
                }
                $fullname .= $category->getName();
                $categories[] = $fullname;
            }
            $audiences = [];
            foreach ($result->getAidAudiences() as $aidAudience) {
                $audiences[] = $aidAudience->getName();
            }
            $types = [];
            foreach ($result->getAidTypes() as $aidType) {
                $types[] = $aidType->getName();
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
                'instructors' => $instructors,
                'programs' => $programs,
                'description' => $result->getDescription(),
                'eligibility' => $result->getEligibility(),
                'perimeter' => $result->getPerimeter() ? $result->getPerimeter()->getName() : null,
                'mobilization_steps' => $steps,
                'origin_url' => $result->getOriginUrl(),
                'categories' => $categories,
                'is_call_for_project' => $result->isIsCallForProject(),
                'application_url' => $result->getApplicationUrl(),
                'targeted_audiences' => $audiences,
                'aid_types' => $types,
                'is_charged' => $result->isIsCharged(),
                'destinations' => $destinations,
                'start_date' => $result->getDateStart() ? $result->getDateStart()->format('Y-m-d') : null,
                'predeposit_date' => $result->getDatePredeposit() ? $result->getDatePredeposit()->format('Y-m-d') : null,
                'submission_deadline' => $result->getDateSubmissionDeadline() ? $result->getDateSubmissionDeadline()->format('Y-m-d') : null,
                'subvention_rate_lower_bound' => $result->getSubventionRateMin(),
                'subvention_rate_upper_bound' => $result->getSubventionRateMax(),
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
                'project_references' => $projectReferences
            ];
        }

        return $resultsSpe;
    }
}
