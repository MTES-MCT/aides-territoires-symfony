<?php

namespace App\Controller\Api\Backer;

use App\Controller\Api\ApiController;
use App\Entity\Backer\Backer;
use App\Repository\Backer\BackerRepository;
use App\Service\Various\ParamService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class BackerController extends ApiController
{
    #[Route('/api/backers/', name: 'api_backer_backer', priority: 5)]
    public function index(
        BackerRepository $backerRepository,
        ParamService $paramService
    ): JsonResponse
    {
        // les filtres
        $params = [
            'active' => true
        ];
        $q = $this->requestStack->getCurrentRequest()->get('q', null);
        if ($q) {
            $params['nameLike'] = $q;
        }
        $hasFinancedAids = $this->requestStack->getCurrentRequest()->get('has_financed_aids', null);
        if ($hasFinancedAids) {
            $params['hasFinancedAids'] = $this->stringToBool($hasFinancedAids);
        }
        $hasPublishedFinancedAids = $this->requestStack->getCurrentRequest()->get('has_published_financed_aids', null);
        if ($hasPublishedFinancedAids) {
            $params['hasPublishedFinancedAids'] = $this->stringToBool($hasPublishedFinancedAids);
        }

        // requete pour compter sans la pagination
        $count = $backerRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();
    
        $results = $backerRepository->findCustom($params);

        // spécifique backer
        $resultsSpe = [];
        /** @var Backer $result */
        foreach ($results as $result) {
            $resultsSpe[] = [
                'id' => $result->getId().'-'.$result->getSlug(),
                'text' => $result->getName(),
                'perimeter' => $result->getPerimeter() ? $result->getPerimeter()->getName() : null,
                'logo' => $result->getLogo() ? $paramService->get('cloud_image_url').$result->getLogo() : null,
            ];
        }

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
}
