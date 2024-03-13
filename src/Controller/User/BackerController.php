<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Backer\Backer;
use App\Form\Backer\BackerEditType;
use App\Repository\Backer\BackerRepository;
use App\Service\Backer\BackerService;
use App\Service\Image\ImageService;
use App\Service\User\UserService;
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
        ImageService $imageService,
        BackerService $backerService,
        UserService $userService
    )
    {
        // regarde si backer
        $backer = $backerRepository->findOneBy([
            'slug' => $slug
        ]);
        if (!$backer instanceof Backer) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // l'utilisateur
        $user = $userService->getUserLogged();

        // regarde si l'utilisateur à le droit d'être ici
        if (!$backerService->userCanSee($user, $backer)) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        $userCanEdit = $backerService->userCanEdit($user, $backer);

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
            'user_backer_id' => $backer instanceof Backer ? $backer->getId() : null,
            'userCanEdit' => $userCanEdit
        ]);
    }

    #[Route('/comptes/porteur/utilisateurs/{slug}/', name: 'app_user_backer_users', requirements: ['slug' => '[a-zA-Z0-9\-_]*'])]
    public function users(
        $slug,
        RequestStack $requestStack,
        BackerRepository $backerRepository,
    )
    {
        // regarde si backer
        $backer = $backerRepository->findOneBy([
            'slug' => $slug
        ]);

        // fil arianne
        $this->breadcrumb->add(
            'Mon compte',
            $this->generateUrl('app_user_dashboard')
        );
        $this->breadcrumb->add(
            'Gestion utilisateurs porteur d\'aide'
        );

        return $this->render('user/backer/users.html.twig', [
            'backer' => $backer,
            'user_backer' => true,
            'user_backer_id' => $backer instanceof Backer ? $backer->getId() : null
        ]);
    }
}