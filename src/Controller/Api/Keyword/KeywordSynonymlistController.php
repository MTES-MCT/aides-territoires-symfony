<?php

namespace App\Controller\Api\Keyword;

use App\Controller\Api\ApiController;
use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Program\Program;
use App\Repository\Keyword\KeywordSynonymlistRepository;
use App\Repository\Program\ProgramRepository;
use App\Service\Keyword\KeywordSynonymlistService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class KeywordSynonymlistController extends ApiController
{
    #[Route('/api/synonymlists/', name: 'api_keyword_synonymlist_list', priority: 5)]
    public function index(
        KeywordSynonymlistRepository $keywordSynonymlistRepository,
        KeywordSynonymlistService $keywordSynonymlistService
    ): JsonResponse
    {
        // les filtres
        $params = [];

        // requete pour compter sans la pagination
        $count = $keywordSynonymlistRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();
        $params['orderBy'] = ['sort' => 'ks.name', 'order' => 'ASC'];

        $results = $keywordSynonymlistRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        /** @var KeywordSynonymlist $result */
        foreach ($results as $result) {
            // TODO n'importe quoi sur l'id ?
            $resultsSpe[] = [
                'id' => $result->getId().'-synonyms-'.$result->getName(),
                'text' => $keywordSynonymlistService->getSmartName($result),
                'name' => $result->getName(),
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

    #[Route('/api/synonymlists/classic-list/', name: 'api_keyword_synonymlist_list_classic', priority: 5)]
    public function classic(
        KeywordSynonymlistRepository $keywordSynonymlistRepository,
        KeywordSynonymlistService $keywordSynonymlistService
    ): JsonResponse
    {
        // les filtres
        $params = [];

        // requete pour compter sans la pagination
        $count = $keywordSynonymlistRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['orderBy'] = ['sort' => 'ks.name', 'order' => 'ASC'];
        
        $results = $keywordSynonymlistRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        /** @var KeywordSynonymlist $result */
        foreach ($results as $result) {
            $resultsSpe[] = [
                'id' => $result->getId(),
                'text' => $result->getName(),
            ];
        }


        // la réponse
        $response =  new JsonResponse($resultsSpe, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }

}