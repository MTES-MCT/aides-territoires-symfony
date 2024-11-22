<?php

namespace App\Controller\Admin\Perimeter;

use App\Controller\Admin\DashboardController;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class PerimeterImportController extends DashboardController
{
    #[Route('/admin/perimeter/import/{id}', name: 'admin_perimeter_import', requirements: ['id' => '[0-9]+'])]
    public function importPerimeters(
        int $id,
        ManagerRegistry $managerRegistry
    ): Response {
        $perimeterImport = $managerRegistry->getRepository(PerimeterImport::class)->find($id);

        return $this->render('admin/perimeter/import.html.twig', [
            'perimeterImport' => $perimeterImport,
        ]);
    }

    #[Route('/admin/perimeter/import/import-item', name: 'admin_perimeter_import_ajax')]
    public function ajaxImportPerimeterItem(
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry
    ): JsonResponse {
        ini_set('max_execution_time', 60 * 60);
        ini_set('memory_limit', '8G');
        $nbToDo = 1;

        $current = $requestStack->getCurrentRequest()->request->get('current', null);
        if ($current === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Paramètre manquant'
            ]);
        }
        $perimeterImport = $managerRegistry
            ->getRepository(PerimeterImport::class)
            ->find($requestStack->getCurrentRequest()->request->get('idPerimeterImport'));
        if (!$perimeterImport instanceof PerimeterImport) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Périmètre introuvable'
            ]);
        }

        $notFound = [];
        for ($i = $current; $i < $current + $nbToDo; $i++) {
            try {
                if (!isset($perimeterImport->getCityCodes()[$i])) {
                    continue;
                }
                $perimeterToAdd = $managerRegistry->getRepository(Perimeter::class)->findOneBy([
                    'insee' => $perimeterImport->getCityCodes()[$i]
                ]);
                if (!$perimeterToAdd instanceof Perimeter) {
                    $notFound[] = $perimeterImport->getCityCodes()[$i];
                    continue;
                }

                // ajoute le périmètre à la liste des périmètres enfants du périmètre adhoc
                $perimeterImport->getAdhocPerimeter()->addPerimetersFrom($perimeterToAdd);

                // va recuperer tous les parents du périmètre à ajouter et met le perimètre adhoc dedans
                foreach ($perimeterToAdd->getPerimetersTo() as $parentToAdd) {
                    $perimeterImport->getAdhocPerimeter()->addPerimetersTo($parentToAdd);
                }
            } catch (\Exception $e) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        }

        try {
            // si terminé
            if ($current + $nbToDo >= count($perimeterImport->getCityCodes())) {
                $perimeterImport->setIsImported(true);
                $perimeterImport->setTimeImported(new \DateTime(date('Y-m-d H:i:s')));
            }

            // sauvegarde
            $managerRegistry->getManager()->persist($perimeterImport->getAdhocPerimeter());
            $managerRegistry->getManager()->flush();

            // retour
            return new JsonResponse([
                'status' => 'success',
                'message' => 'OK',
                'current' => $current + $nbToDo,
                'notFound' => $notFound
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
