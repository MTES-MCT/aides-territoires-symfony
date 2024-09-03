<?php

namespace App\Controller\Api\Aid;

use App\Controller\Api\ApiController;
use App\Repository\Organization\OrganizationTypeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class AidAudienceController extends ApiController
{
    #[Route('/api/aids/audiences/', name: 'api_aid_audiences', priority: 5)]
    public function index(
        OrganizationTypeRepository $organizationTypeRepository,
    ): JsonResponse {
        $params = [];
        // requete pour compter sans la pagination
        $count = $organizationTypeRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $organizationTypeRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        foreach ($results as $result) {
            $resultsSpe[] = [
                'id' => $result->getSlug(),
                'name' => $result->getName(),
                'type' => $result->getOrganizationTypeGroup() ? $result->getOrganizationTypeGroup()->getName() : null
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
