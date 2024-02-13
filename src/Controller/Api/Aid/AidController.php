<?php

namespace App\Controller\Api\Aid;

use App\Controller\Api\ApiController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Perimeter\Perimeter;
use App\Repository\Aid\AidRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        AidSearchFormService $aidSearchFormService
    ): JsonResponse
    {
        $aidSearchClass = $aidSearchFormService->getAidSearchClass();
        // parametres pour requetes aides
        $aidParams = [
            'showInSearch' => true,
        ];
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));

        // $params = [];
        // $params['showInSearch'] = true;
        // // $params['orderBy'] = ['sort' => 'a.id', 'order' => 'DESC'];

        // $text = $this->requestStack->getCurrentRequest()->get('text', null);
        // if (!empty($text)) {
        //     $params['keyword'] = $text;
        // }

        // $targetedAudiences = $this->requestStack->getCurrentRequest()->get('targeted_audiences', null);
        // if (!empty($targetedAudiences)) {
        //     $params['organizationTypeSlugs'] = [$targetedAudiences]; // malgré le nom il n'y a qu'une seul audience dans le filtre
        // }

        // $applyBefore = $this->requestStack->getCurrentRequest()->get('apply_before', null);
        // if (!empty($applyBefore)) {
        //     $params['applyBefore'] = new \DateTime(date($applyBefore));
        // }

        // $publishedAfter = $this->requestStack->getCurrentRequest()->get('published_after', null);
        // if (!empty($publishedAfter)) {
        //     $params['publishedAfter'] = new \DateTime(date($publishedAfter));
        // }

        // $aidType = $this->requestStack->getCurrentRequest()->get('aid_type', null);
        // if (!empty($aidType)) {
        //     $params['aidTypeGroup'] = $this->managerRegistry->getRepository(AidTypeGroup::class)->findOneBy(['slug' => $this->stringService->getSlug($aidType)]);
        // }

        // $params['aidTypes'] = [];
        // $financialAids = $this->requestStack->getCurrentRequest()->get('financial_aids', null);
        // if (!empty($financialAids)) {
        //     $params['aidTypes'][] = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['slug' => $this->stringService->getSlug($financialAids)]);
        // }

        // $technicalAids = $this->requestStack->getCurrentRequest()->get('technical_aids', null);
        // if (!empty($technicalAids)) {
        //     $params['aidTypes'][] = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['slug' => $this->stringService->getSlug($technicalAids)]);
        // }
        // if (count($params['aidTypes']) == 0) {
        //     unset($params['aidTypes']);
        // }

        // $mobilizationStep = $this->requestStack->getCurrentRequest()->get('mobilization_step', null);
        // if (!empty($mobilizationStep)) {
        //     $params['aidStep'] = $this->managerRegistry->getRepository(AidStep::class)->findOneBy(['slug' => $this->stringService->getSlug($mobilizationStep)]);
        // }

        // $destinations = $this->requestStack->getCurrentRequest()->get('destinations', null);
        // if (!empty($destinations)) {
        //     $params['aidDestination'] = $this->managerRegistry->getRepository(AidDestination::class)->findOneBy(['slug' => $this->stringService->getSlug($destinations)]);
        // }

        // $recurrence = $this->requestStack->getCurrentRequest()->get('recurrence', null);
        // if (!empty($recurrence)) {
        //     $params['aidRecurrence'] = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => $this->stringService->getSlug($recurrence)]);
        // }

        // $callForProjectsOnly = $this->requestStack->getCurrentRequest()->get('call_for_projects_only', null);
        // if (!empty($callForProjectsOnly)) {
        //     $params['isCallForProject'] = $this->stringToBool($callForProjectsOnly);
        // }

        // $isCharged = $this->requestStack->getCurrentRequest()->get('is_charged', null);
        // if (!empty($isCharged)) {
        //     $params['isCharged'] = $this->stringToBool($isCharged);
        // }

        // $perimeter = $this->requestStack->getCurrentRequest()->get('perimeter', null);
        // if (!empty($perimeter)) {
        //     $params['perimeter'] = $this->managerRegistry->getRepository(Perimeter::class)->find((int) $perimeter);
        // }

        // requete pour compter sans la pagination
        $countParams = $aidParams;
        $count = $aidRepository->countAfterSelect($countParams);

        // requete pour les résultats avec la pagination
        $aidParams['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $aidParams['maxResults'] = $this->getItemsPerPage();
        
        $results = $aidRepository->findCustom($aidParams);

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
    #[Route('/api/aids/{slug}/', name: 'api_aid_by_slug', priority: 5)]
    public function bySlug(
        $slug,
        AidRepository $aidRepository,
        AidService $aidService
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
            ];
        }

        return $resultsSpe;
    }
}