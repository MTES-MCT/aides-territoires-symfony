<?php

namespace App\Controller\Admin\Perimeter;

use App\Controller\Admin\DashboardController;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use App\Form\Admin\Perimeter\CombineType;
use App\Form\Admin\Perimeter\ImportCsvInseeType;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Sabberworm\CSS\Property\Import;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PerimeterController extends DashboardController
{
    #[Route('/admin/perimeter/{id}/combiner', name: 'admin_perimeter_combine', requirements: ['id' => '[0-9]+'])]
    public function combine(
        $id,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack
    ): Response
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


    #[Route('/admin/perimeter/{id}/import-insee', name: 'admin_perimeter_import_insee', requirements: ['id' => '[0-9]+'])]
    public function importInsee(
        $id,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        UserService $userService
    ): Response
    {
        // le perimetre
        /** @var Perimeter $perimeter */
        $perimeter = $managerRegistry->getRepository(Perimeter::class)->find($id);

        $backUrl = $this->adminUrlGenerator
        ->setController(PerimeterCrudController::class)
        ->setAction('edit')
        ->setEntityId($perimeter->getId())
        ->generateUrl();

        // formulaire import code insee
        $codesInseeNotFound = [];
        $form = $this->createForm(ImportCsvInseeType::class);
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $fileCsv = $form->get('fileCsv')->getData();
                // lecture du fichier csv
                $file = fopen($fileCsv->getPathname(), 'r');
                
                $perimeterImport = new PerimeterImport();
                $perimeterImport->setAdhocPerimeter($perimeter);
                $perimeterImport->setAskProcessing(true);
                $perimeterImport->setAuthor($userService->getUserLogged());


                while (($line = fgetcsv($file)) !== false) {
                    $perimeterImport->addCityCode($line[0]);
                    // $perimeterToAdd = $managerRegistry->getRepository(Perimeter::class)->findOneBy([
                    //     'insee' => $line[0],
                    //     'scale' => Perimeter::SCALE_COMMUNE
                    // ]);
                    // if ($perimeterToAdd instanceof Perimeter) {
                    //     $perimeter->addPerimetersFrom($perimeterToAdd);
                    // } else {
                    //     $codesInseeNotFound[] = $line[0];
                    // }
                }

                // sauvegarde
                $this->managerRegistry->getManager()->persist($perimeterImport);
                $this->managerRegistry->getManager()->flush();

                $this->addFlash('success', 'Import demandé. Vous recevrez un mail lorsque l\'import sera terminé.');
            } else {
                $this->addFlash('error', 'Impossible de modifier le périmètre.');
                return $this->redirectToRoute('admin_perimeter_import_insee', [
                    'id' => $perimeter->getId()
                ]);
            }
        }
        // rendu template
        return $this->render('admin/perimeter/impport_insee.html.twig', [
            'form' => $form,
            'perimeter' => $perimeter,
            'backUrl' => $backUrl,
            'codesInseeNotFound' => $codesInseeNotFound
        ]);
    }
}