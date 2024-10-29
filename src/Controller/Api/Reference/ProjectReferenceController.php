<?php

namespace App\Controller\Api\Reference;

use App\Controller\Api\ApiController;
use App\Entity\Reference\ProjectReference;
use App\Entity\Reference\ProjectReferenceCategory;
use App\Repository\Reference\ProjectReferenceCategoryRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ProjectReferenceController extends ApiController
{
    #[Route('/api/project-references/', name: 'api_project_references', priority: 5)]
    public function index(
        ProjectReferenceRepository $projectReferenceRepository,
        ProjectReferenceCategoryRepository $projectReferenceCategoryRepository,
    ): JsonResponse {
        // les filtres
        $params = [];
        $q = $this->requestStack->getCurrentRequest()->get('q', null);
        if ($q) {
            $params['nameLike'] = $q;
        }
        $projectReferenceCategoryId =
            $this->requestStack->getCurrentRequest()->get('project_reference_category_id', null);
        if ($projectReferenceCategoryId) {
            try {
                $projectReferenceCategory = $projectReferenceCategoryRepository->find($projectReferenceCategoryId);
                if ($projectReferenceCategory instanceof ProjectReferenceCategory) {
                    $params['projectReferenceCategory'] = $projectReferenceCategory;
                }
            } catch (\Exception $e) {
            }
        }

        // requete pour compter sans la pagination
        $count = $projectReferenceRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $projectReferenceRepository->findCustom($params);

        // on serialize pour ne garder que les champs voulus
        $results = $this->serializerInterface->serialize(
            $results,
            static::SERIALIZE_FORMAT,
            ['groups' => ProjectReference::API_GROUP_LIST]
        );

        // le retour
        $data = [
            'count' => $count,
            'previous' => $this->getPrevious(),
            'next' => $this->getNext($count),
            'results' => json_decode($results)
        ];

        // la réponse
        $response =  new JsonResponse($data, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }
}
