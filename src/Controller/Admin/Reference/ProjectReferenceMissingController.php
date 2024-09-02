<?php

namespace App\Controller\Admin\Reference;

use App\Repository\Reference\ProjectReferenceMissingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class ProjectReferenceMissingController extends AbstractController
{
    #[Route('/admin/projets-reference-missing/ajax-ux-autocomplete/', name: 'app_admin_project_reference_missing_ajax_ux_autocomplete')]
    public function ajaxUxAutocomplete(
        ProjectReferenceMissingRepository $projectReferenceMissingRepository,
        RequestStack $requestStack
    ): JsonResponse
    {
        $query = $requestStack->getCurrentRequest()->get('query', null);

        // recherche les projets référents correspondants
        $projectReferenceMissings = $projectReferenceMissingRepository->findCustom([
            'nameLike' => $query,
            'orderBy' => [
                'sort' => 'pr.name',
                'order' => 'ASC'
            ]
        ]);

        $results = [];
        foreach ($projectReferenceMissings as $projectReference) {
            $results[] = [
                'value' => $projectReference->getName(),
                'text' => $projectReference->getName()
            ];
        }

        $return = [
            'results' => $results
        ];

        return new JsonResponse($return);
    }
}