<?php

namespace App\Controller\Api\Backer;

use App\Controller\Api\ApiController;
use App\Repository\Backer\BackerRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class BackerController extends ApiController
{
    #[Route('/api/backers/', name: 'api_backer_backer', priority: 5)]
    public function index(
        BackerRepository $backerRepository,
    ): JsonResponse
    {
        // les filtres
        $params = [];
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
        foreach ($results as $result) {
            $resultsSpe[] = [
                'id' => $result->getId().'-'.$result->getSlug(),
                'text' => $result->getName(),
                'perimeter' => $result->getPerimeter() ? $result->getPerimeter()->getName() : null
            ];
        }
        // on serialize pour ne garder que les champs voulus
        // $results = $this->serializerInterface->serialize($results, static::SERIALIZE_FORMAT, ['groups' => Backer::API_GROUP_LIST]);
        
        // le retour
        $data = [
            'count' => $count,
            'previous' => $this->getPrevious(),
            'next' => $this->getNext($count),
            'results' => $resultsSpe
            // 'results' => json_decode($results)
        ];

        // la réponse
        $response =  new JsonResponse($data, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }
}