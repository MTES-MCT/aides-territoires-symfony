<?php

namespace App\Controller\Api\Aid;

use App\Controller\Api\ApiController;
use App\Repository\Aid\AidTypeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class AidTypeController extends ApiController
{
    #[Route('/api/aids/types/', name: 'api_aid_types', priority: 5)]
    public function index(
        AidTypeRepository $aidTypeRepository,
    ): JsonResponse {
        $params = [];
        // requete pour compter sans la pagination
        $count = $aidTypeRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $aidTypeRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        foreach ($results as $result) {
            $typeFull = [];
            if ($result->getAidTypeGroup()) {
                $typeFull = [
                    'id' => $result->getAidTypeGroup()->getId(),
                    'slug' => $result->getAidTypeGroup()->getSlug(),
                    'name' => $result->getAidTypeGroup()->getName()
                ];
            }
            $resultsSpe[] = [
                'id' => $result->getSlug(),
                'slug' => $result->getSlug(),
                'name' => $result->getName(),
                'type' => $result->getAidTypeGroup() ? $result->getAidTypeGroup()->getName() : null,
                'type_full' => $typeFull
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
