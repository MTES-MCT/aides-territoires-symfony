<?php

namespace App\Controller\Admin\Perimeter;

use App\Controller\Admin\DashboardController;
use App\Entity\Perimeter\Perimeter;
use App\Form\Admin\Perimeter\CombineType;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class PerimeterController extends DashboardController
{
    #[Route('/admin/perimeter/{id}/combiner', name: 'admin_perimeter_combine', requirements: ['id' => '[0-9]+'])]
    public function combine(
        $id,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack
    )
    {
        // le perimetre
        /** @var Perimeter $perimeter */
        $perimeter = $managerRegistry->getRepository(Perimeter::class)->find($id);

        $backUrl = $this->adminUrlGenerator
        ->setController(PerimeterCrudController::class)
        ->setAction('edit')
        ->setEntityId($perimeter->getId())
        ->generateUrl();

        // formulaire combiner
        $formCombine = $this->createForm(CombineType::class);
        $formCombine->handleRequest($requestStack->getCurrentRequest());
        if ($formCombine->isSubmitted()) {
            if ($formCombine->isValid()) {
                $perimetersToAdd = $formCombine->get('perimetersToAdd')->getData();
                foreach ($perimetersToAdd as $perimeterToAdd) {
                    $perimeter->addPerimetersFrom($perimeterToAdd);
                }

                $perimetersToRemove = $formCombine->get('perimetersToRemove')->getData();
                foreach ($perimetersToRemove as $perimeterToRemove) {
                    $perimeter->removePerimetersFrom($perimeterToRemove);
                }

                // sauvegarde
                $this->managerRegistry->getManager()->persist($perimeter);
                $this->managerRegistry->getManager()->flush();

                // message
                $this->addFlash('success', 'Le périmètre a bien été modifié.');

                // redirection
                return $this->redirect($backUrl);
            } else {
                $this->addFlash('error', 'Impossible de modifier le périmètre.');
            }
        }




        // rendu template
        return $this->render('admin/perimeter/combine.html.twig', [
            'formCombine' => $formCombine,
            'perimeter' => $perimeter,
            'backUrl' => $backUrl
        ]);
    }
}