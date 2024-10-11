<?php

namespace App\Controller;

use App\Exception\NotFoundException\UserRegisterConfirmationNotFoundException;
use App\Form\Security\LoginType;
use App\Repository\User\UserRegisterConfirmationRepository;
use App\Service\Security\ProConnectService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route(priority: 1)]
class SecurityController extends FrontController
{
    #[Route(path: '/comptes/connexion/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // si déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $formLogin = $this->createForm(LoginType::class);
        if ($lastUsername) {
            $formLogin->get('_username')->setData($lastUsername);
        }

        return $this->render('security/login.html.twig', [
            'formLogin' => $formLogin->createView(),
            'error' => $error
        ]);
    }

    #[Route('/comptes/connexion/proconnect/', name: 'app_login_proconnect')]
    public function loginByPronnect(
        ProConnectService $proConnectService
    ) : Response {
        // On recupère les informations de proconnect
        $autorizationEndpoint = $proConnectService->getAuthorizationEndpoint();
// dd($autorizationEndpoint);
        return new RedirectResponse($autorizationEndpoint);
        // génération state et nonce

        // return new Response('ok');
    }

    #[Route('/comptes/connexion/{token}', name: 'app_user_user_register_confirmation')]
    public function registerConfirmation(
        $token,
        UserRegisterConfirmationRepository $userRegisterConfirmationRepository,
        ManagerRegistry $managerRegistry,
        Security $security
    ): Response {
        // check token
        $userRegisterConfirmation = $userRegisterConfirmationRepository->findOneBy(
            [
                'token' => $token
            ]
        );
        if (!$userRegisterConfirmation) {
            throw new UserRegisterConfirmationNotFoundException('Ce lien n\'existe pas');
        }

        // le lien n'as pas été utilisé
        if (!$userRegisterConfirmation->getTimeUse()) {
            // enregistre l'utilisation
            $userRegisterConfirmation->setTimeUse(new \DateTime(date('Y-m-d H:i:s')));
            $managerRegistry->getManager()->persist($userRegisterConfirmation);
            $managerRegistry->getManager()->flush();

            // log le user
            if ($userRegisterConfirmation->getUser()) {
                $flashMessage = $userRegisterConfirmation->getUser()->getTimeLastLogin()
                    ? 'Vous êtes maintenant connecté. Bienvenue ! Pourriez-vous prendre quelques secondes pour mettre à jour votre profil ?'
                    : 'Vous êtes maintenant connecté. Bienvenue !';
                $security->login($userRegisterConfirmation->getUser(), 'form_login', 'main');
                // message success
                $this->tAddFlash(
                    FrontController::FLASH_SUCCESS,
                    $flashMessage
                );

                // redirection
                return $this->redirectToRoute('app_user_dashboard');
            }
        }

        // le lien a déjà été utilisé, on affiche une erreur
        return $this->render('security/register-confirmation.html.twig', []);
    }

    #[Route(path: '/admin/connexion/', name: 'app_login_admin')]
    public function loginAdmin(AuthenticationUtils $authenticationUtils): Response
    {
        // si déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $formLogin = $this->createForm(LoginType::class);
        if ($lastUsername) {
            $formLogin->get('_username')->setData($lastUsername);
        }

        return $this->render('security/login.html.twig', [
            'formLogin' => $formLogin->createView(),
            'error' => $error,
            'loginAdmin' => true
        ]);
    }


    // Url pour la connexion à l'API
    #[Route(path: '/api/connexion/', name: 'app_login_api')]
    public function loginApi(AuthenticationUtils $authenticationUtils, RequestStack $requestStack): JsonResponse
    {
        if ($requestStack->getMainRequest()->getMethod() !== 'POST') {
            return new JsonResponse('Cette page doit être appellée en POST', 405);
        }
        if (!$requestStack->getMainRequest()->headers->get('X-AUTH-TOKEN')) {
            return new JsonResponse('Veuillez ajouter votre X-AUTH-TOKEN dans les HEADERS', 401);
        }
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
