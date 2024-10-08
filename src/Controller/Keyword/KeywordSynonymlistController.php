<?php

namespace App\Controller\Keyword;

use App\Controller\FrontController;
use App\Repository\Keyword\KeywordSynonymlistRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class KeywordSynonymlistController extends FrontController
{
    #[Route('/keyword-synonymlist/ajax-autocomplete/', name: 'app_keyword_kewyord_synonymlist_ajax_autocomplete')]
    public function ajaxAutocomplete(
        RequestStack $requestStack,
        KeywordSynonymlistRepository $keywordSynonymlistRepository
    ): JsonResponse {
        $query = $requestStack->getCurrentRequest()->get('query', null);
        if ($query) {
            $query = trim(strip_tags((string) $query));
        }
        $keywords = $keywordSynonymlistRepository->findCustom(
            [
                'nameLike' => $query,
                'orderBy' => [
                    'sort' => 'ks.name',
                    'order' => 'ASC'
                ]
            ]
        );

        $results = [];
        foreach ($keywords as $keyword) {
            $results[] = [
                'value' => $keyword->getName(),
                'text' => $keyword->getName()
            ];
        }

        if (count($keywords) == 0) {
            $results[] = [
                'value' => $query,
                'text' => $query
            ];
        }

        return new JsonResponse(
            [
                'results' => $results
            ]
        );
    }
}
