<?php

namespace App\Controller\Reference;

use App\Controller\FrontController;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Organization\OrganizationType;
use App\Entity\Reference\ProjectReference;
use App\Form\Reference\ProjectReferenceSearchType;
use App\Repository\Aid\AidRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\Project\ProjectValidatedRepository;
use App\Repository\Reference\KeywordReferenceRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Service\Aid\AidService;
use App\Service\Reference\ReferenceService;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route(priority: 5)]
class KeywordReferenceController extends FrontController
{
    #[Route('/keyword-references/ajax-ux-autocomplete/', name: 'app_keyword_reference_ajax_ux_autocomplete')]
    public function ajaxUxAutocomplete(
        KeywordReferenceRepository $keywordReferenceRepository,
        RequestStack $requestStack
    ): JsonResponse {
        $query = $requestStack->getCurrentRequest()->get('query', null);

        $keywordReferences = $keywordReferenceRepository->findCustom([
            'nameLike' => $query,
            'onlyParent' => true,
            'orderBy' => [
                'sort' => 'kr.name',
                'order' => 'ASC'
            ]
        ]);

        $results = [];
        foreach ($keywordReferences as $keywordReference) {
            $results[] = [
                'value' => $keywordReference->getName(),
                'text' => ucfirst($keywordReference->getName())
            ];
        }
        $return = [
            'results' => $results
        ];

        return new JsonResponse($return);
    }
}
