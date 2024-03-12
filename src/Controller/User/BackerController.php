<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Backer\Backer;
use App\Form\Backer\BackerEditType;
use App\Repository\Backer\BackerRepository;
use App\Service\Image\ImageService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class BackerController extends FrontController
{
    #[Route('/comptes/porteur/edition/{slug}/', name: 'app_user_backer_edit', requirements: ['slug' => '[a-zA-Z0-9\-_]*'])]
    public function edit(
        $slug,
        RequestStack $requestStack,
        BackerRepository $backerRepository,
        ImageService $imageService
    )
    {
        // regarde si backer
        $backer = $backerRepository->findOneBy([
            'slug' => $slug
        ]);

        // formulaire edition porteur
        $form = $this->createForm(BackerEditType::class, $backer);
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // traitement image
                $logoFile = $form->get('logoFile')->getData();
                if ($logoFile instanceof UploadedFile) {
                    $backer->setLogo(Backer::FOLDER.'/'.$imageService->getSafeFileName($logoFile->getClientOriginalName()));
                    $imageService->sendUploadedImageToCloud($logoFile, Backer::FOLDER, $backer->getLogo());
                }
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Le formulaire contient des erreurs.');
            }
        }

        // fil arianne
        $this->breadcrumb->add(
            'Mon compte',
            $this->generateUrl('app_user_dashboard')
        );
        $this->breadcrumb->add(
            'Edition fiche porteur d\'aide'
        );

        return $this->render('user/backer/edit.html.twig', [
            'backer' => $backer,
            'form' => $form,
            'user_backer' => true,
            'user_backer_id' => $backer instanceof Backer ? $backer->getId() : null
        ]);
    }
}