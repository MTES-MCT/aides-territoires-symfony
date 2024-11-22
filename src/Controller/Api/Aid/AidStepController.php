<?php

namespace App\Controller\Api\Aid;

use App\Controller\Api\ApiController;
use App\Repository\Aid\AidStepRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class AidStepController extends ApiController
{
    #[Route('/api/aids/steps/', name: 'api_aid_steps', priority: 5)]
    public function index(
        AidStepRepository $aidStepRepository,
    ): JsonResponse {
        $params = [];
        // requete pour compter sans la pagination
        $count = $aidStepRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $aidStepRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        foreach ($results as $result) {
            $resultsSpe[] = [
                'id' => $result->getSlug(),
                'slug' => $result->getSlug(),
                'name' => $result->getName()
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
