<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Project\Project;
use App\Entity\User\ApiTokenAsk;
use App\Form\User\ApiTokenAskCreateType;
use App\Form\User\DeleteType;
use App\Form\User\TransfertAidType;
use App\Form\User\TransfertProjectType;
use App\Form\User\UserProfilType;
use App\Repository\Aid\AidRepository;
use App\Repository\Log\LogUserLoginRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\User\ApiTokenAskRepository;
use App\Service\Email\EmailService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ParameterController extends FrontController
{
    const NB_HISTORY_LOG_BY_PAGE = 20;

    #[Route('/comptes/monprofil/', name: 'app_user_parameter_profil')]
    public function profil(UserPasswordHasherInterface $userPasswordHasher, UserService $userService, ManagerRegistry $managerRegistry, RequestStack $requestStack): Response
    {
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mon profil");
        $user = $userService->getUserLogged();

        $form = $this->createForm(UserProfilType::class, $user);
        $form->handleRequest($requestStack->getCurrentRequest());
        if($form->isSubmitted()){

            if ($form->isValid()){
                // sauvegarder le user
                $newPassword = $form->get('newPassword')->getData();

                if ($newPassword) {
                    $hashedPassword = $userPasswordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                }
                    
                // sauvegarde
                $managerRegistry->getManager()->persist($user);
                $managerRegistry->getManager()->flush();
                
                // message
                $this->tAddFlash(
                    FrontController::FLASH_SUCCESS,
                    'Vos modifications ont été enregistrées avec succès.'
                );

                // redirection
                return $this->redirectToRoute('app_user_parameter_profil');
            } else {
                // message
                $this->tAddFlash(
                    FrontController::FLASH_ERROR,
                    'Les données saisies ne permettent pas la modification de votre profil.'
                );
            }
        }

        return $this->render('user/parameter/profil.html.twig', [
            'form' => $form->createView()
        ]);
    }

    
    #[Route('/comptes/api-token/', name: 'app_user_parameter_api_token')]
    public function apiToken(
        UserService $userService,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        ApiTokenAskRepository $apiTokenAskRepository
    ): Response
    {
        // recupere le user
        $user = $userService->getUserLogged();

        // formulaire demande api token
        $apiTokenAsk = $apiTokenAskRepository->findOneBy(['user'=>$user]);
        if (!$apiTokenAsk) {
            $apiTokenAsk = new ApiTokenAsk();
            $apiTokenAsk->setUser($user);
        }
        $form = $this->createForm(ApiTokenAskCreateType::class, $apiTokenAsk);
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // sauvegarde
                $managerRegistry->getManager()->persist($apiTokenAsk);
                $managerRegistry->getManager()->flush();

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Votre demande de clé API a été enregistrée avec succès.'
                );

                // redirection
                return $this->redirectToRoute('app_user_parameter_api_token');
            } else  {
                // message
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Les données saisies ne permettent pas la demande de clé API.'
                );
            }
        }

        // fil arianne
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Clé API');
        
        // rendu template
        return $this->render('user/parameter/api_token.html.twig', [
            'form' => $form,
            'apiTokenAsk' => $apiTokenAsk
        ]);
    }
    
    #[Route('/comptes/journal-de-connexion/', name: 'app_user_parameter_history_log')]
    public function historyLog(UserService $userService, ManagerRegistry $managerRegistry, RequestStack $requestStack, LogUserLoginRepository $logUserLoginRepository): Response
    {
        $this->breadcrumb->add('Mon compte',$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Mon journal de connexion');
        
        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);

        $user = $userService->getUserLogged();
        $logins = $user->getLogUserLogins();

        $form = $this->createFormBuilder($user)
            ->add('save',SubmitType::class,['label'=>'Oui'])
            ->getForm();
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            foreach ($logins as $login) {
                $entityManager->remove($login);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Vos modifications ont été enregistrées avec succès.');
            return $this->redirectToRoute('app_user_parameter_history_log');
        }

        $logsParams = array();
        $logsParams['user'] = $user;
        $logsParams['action'] = 'login';
        $logsParams['limit'] = $params['limit'] ?? 3;
        
        // le paginateur
        $adapter = new QueryAdapter($logUserLoginRepository->getQuerybuilder($logsParams));
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_HISTORY_LOG_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        return $this->render('user/parameter/history_log.html.twig', [
            // 'logins' => $logins,
            'form' => $form->createView(),
            'my_pager' => $pagerfanta,
        ]);
    }

    #[Route('/comptes/suppression/', name: 'app_user_parameter_delete')]
    public function delete(
        UserService $userService,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorageInterface,
        Session $session,
        EmailService $emailService,
        ProjectRepository $projectRepository,
        AidRepository $aidRepository
    ): Response
    {
        // le user
        $user = $userService->getUserLogged();

        $formTransfertProjects = [];
        $formTransfertAids = [];
        foreach ($user->getOrganizationAccesses() as $organizationAccess) {
            if (!$organizationAccess->getOrganization()) {
                continue;
            }
            $organization = $organizationAccess->getOrganization();
            if (count($organization->getOrganizationAccesses()) > 1) { // l'utilisateur n'est pas seul dans cette organization
                $formTransfertProjects['project-'.$organization->getId()] = $this->createForm(TransfertProjectType::class, null, [
                    'attr' => [
                        'id' => 'formTransfertProject-'.$organization->getId(),
                    ],
                    'organization' => $organization,
                ]);
                $formTransfertProjects['project-'.$organization->getId()]->handleRequest($requestStack->getCurrentRequest());
                if ($formTransfertProjects['project-'.$organization->getId()]->isSubmitted() && $formTransfertProjects['project-'.$organization->getId()]->get('idOrganization')->getData() == $organization->getId()) {
                    if ($formTransfertProjects['project-'.$organization->getId()]->isValid()) {
                        // recupère tous les projets de ce user pour cette organizations
                        $projects = $projectRepository->findBy([
                            'author' => $user,
                            'organization' => $organization,
                        ]);
                        foreach ($projects as $project) {
                            $project->setAuthor($formTransfertProjects['project-'.$organization->getId()]->get('user')->getData());
                            $managerRegistry->getManager()->persist($project);
                        }

                        // sauvegarde
                        $managerRegistry->getManager()->flush();

                        // message
                        $this->addFlash(
                            FrontController::FLASH_SUCCESS,
                            'Votre / Vos projet(s) de l\'organization '.$organization->getName().' ont été transférés avec succès.'
                        );

                        // redirection
                        return $this->redirectToRoute('app_user_parameter_delete');
                    } else {
                        // message
                        $this->addFlash(
                            FrontController::FLASH_ERROR,
                            'Impossible de transférer votre / vos projet(s) de l\'organization '.$organization->getName().' à cet utilisateur'
                        );
                    }
                }
    
                $formTransfertAids['aid-'.$organization->getId()] = $this->createForm(TransfertAidType::class, null, [
                    'attr' => [
                        'id' => 'formTransfertAid-'.$organization->getId(),
                    ],
                    'organization' => $organization,
                ]);
                $formTransfertAids['aid-'.$organization->getId()]->handleRequest($requestStack->getCurrentRequest());
                if ($formTransfertAids['aid-'.$organization->getId()]->isSubmitted() && $formTransfertAids['aid-'.$organization->getId()]->get('idOrganization')->getData() == $organization->getId()) {
                    if ($formTransfertAids['aid-'.$organization->getId()]->isValid()) {
                        // recupère toutes les aides de ce user pour cette organizations
                        $aids = $aidRepository->findBy([
                            'author' => $user,
                            'organization' => $organization,
                        ]);
                        foreach ($aids as $aid) {
                            $aid->setAuthor($formTransfertAids['aid-'.$organization->getId()]->get('user')->getData());
                            $managerRegistry->getManager()->persist($aid);
                        }

                        // sauvegarde
                        $managerRegistry->getManager()->flush();
                        
                        // message
                        $this->addFlash(
                            FrontController::FLASH_SUCCESS,
                            'Votre / Vos aide(s) de l\'organization '.$organization->getName().' ont été transférés avec succès.'
                        );

                        // redirection
                        return $this->redirectToRoute('app_user_parameter_delete');
                    } else {
                        // message
                        $this->addFlash(
                            FrontController::FLASH_ERROR,
                            'Impossible de transférer votre / vos aide(s) de l\'organization '.$organization->getName().' à cet utilisateur'
                        );
                    }
                }
            }
        }        

        // formulaire suppression
        $formDelete = $this->createForm(DeleteType::class);
        $formDelete->handleRequest($requestStack->getCurrentRequest());
        if ($formDelete->isSubmitted()) {
            if ($formDelete->isValid()) {
                // desoptin newsletter
                $emailService->unsubscribeUser($user);

                // suppression
                $managerRegistry->getManager()->remove($user);
                $managerRegistry->getManager()->flush();

                // déconnexion
                $tokenStorageInterface->setToken(null);
                $session->invalidate();

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Votre compte à été supprimé'
                );

                // redirection
                return $this->redirectToRoute('app_home');
            } else {
                // message
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Les données saisies ne permettent pas la suppression de votre compte'
                );
            }
        }

        // fil arianne
        $this->breadcrumb->add(
            'Mon compte',
            $this->generateUrl('app_user_dashboard')
        );
        $this->breadcrumb->add(
            'Supprimer mon compte'
        );

        // rendu template
        return $this->render('user/parameter/delete.html.twig', [
            'formTransfertProjects' => $formTransfertProjects,
            'formTransfertAids' => $formTransfertAids,
            'formDelete' => $formDelete->createView(),
            'user' => $user,
        ]);
    }
    

}
