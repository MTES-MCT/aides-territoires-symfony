<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerUser;
use App\Form\Backer\BackerEditType;
use App\Repository\Backer\BackerRepository;
use App\Service\Backer\BackerService;
use App\Service\Image\ImageService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BackerController extends FrontController
{
    #[Route('/comptes/porteur/creation', name: 'app_user_backer_create')]
    public function create(
        RequestStack $requestStack,
        BackerRepository $backerRepository,
        UserService $userService,
        ImageService $imageService,
        ManagerRegistry $managerRegistry
    ): Response
    {
        $user = $userService->getUserLogged();

        // regarde si l'utilisateur n'as pas déjà un porteur d'aide
        $backers = $backerRepository->findCustom(['user' => $user]);
        if (count($backers) > 0) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // formulaire edition porteur
        $backer = new Backer();
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

                // ajoute le créateur en tant qu'admin
                $backerUser = new BackerUser();
                $backerUser->setUser($user);
                $backerUser->setAdministrator(true);
                $backer->addBackerUser($backerUser);

                // sauvegarde
                $managerRegistry->getManager()->persist($backer);
                $managerRegistry->getManager()->flush();

                // message ok
                $this->addFlash(FrontController::FLASH_SUCCESS, 'La fiche porteur d\'aide a bien été créée.');

                // redirection
                return $this->redirectToRoute('app_user_backer_edit', ['id' => $backer->getId()]);
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Le formulaire contient des erreurs.');
            }
        }
        
        return $this->render('user/backer/create.html.twig', [
            'form' => $form,
            'user_backer' => true,
            'user_backer_create' => true,
        ]);
    }

    #[Route('/comptes/porteur/edition/{id}/', name: 'app_user_backer_edit', requirements: ['id' => '[0-9]+'])]
    public function edit(
        int $id,
        RequestStack $requestStack,
        BackerRepository $backerRepository,
        ImageService $imageService,
        BackerService $backerService,
        UserService $userService,
        ManagerRegistry $managerRegistry
    )
    {
        // regarde si backer
        $backer = $backerRepository->find($id);
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

                // sauvegarde
                $managerRegistry->getManager()->persist($backer);
                $managerRegistry->getManager()->flush();

                // message ok
                $this->addFlash(FrontController::FLASH_SUCCESS, 'La fiche porteur d\'aide a bien été modifiée.');

                // redirection
                return $this->redirectToRoute('app_user_backer_edit', ['id' => $backer->getId()]);
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

    #[Route('/comptes/porteur/utilisateurs/{id}/', name: 'app_user_backer_users', requirements: ['id' => '[0-9]+'])]
    public function users(
        int $id,
        RequestStack $requestStack,
        BackerRepository $backerRepository,
        UserService $userService,
        BackerService $backerService
    )
    {
        // regarde si backer
        $backer = $backerRepository->find($id);
        if (!$backer instanceof Backer) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // le user
        $user = $userService->getUserLogged();

        // regarde si l'utilisateur à le droit d'être ici
        if (!$backerService->userCanSee($user, $backer)) {
            return $this->redirectToRoute('app_user_dashboard');
        }
        
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