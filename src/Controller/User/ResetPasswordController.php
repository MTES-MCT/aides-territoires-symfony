<?php

namespace App\Controller\User;

use App\Entity\User\User;
use App\Exception\NotFoundException\ResetPasswordNotFoundException;
use App\Form\User\ChangePasswordFormType;
use App\Form\User\ResetPasswordRequestFormType;
use App\Service\Email\EmailService;
use App\Service\File\FileService;
use App\Service\Various\ParamService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private ParamService $paramService
    ) {
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route(
        '/comptes/demande-nouveau-mot-de-passe/',
        name: 'app_forgot_password_request'
    )]
    public function request(
        Request $request,
        EmailService $emailService,
        TranslatorInterface $translator,
        RouterInterface $routerInterface
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $emailService,
                $translator,
                $routerInterface
            );
        }

        return $this->render('user/reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
            'no_breadcrumb' => true
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/comptes/demande-nouveau-mot-de-passe/verification-email/', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('user/reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
            'no_breadcrumb' => true
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route(
        '/comptes/demande-nouveau-mot-de-passe/reinitialisation/{token}',
        name: 'app_reset_password'
    )]
    public function reset(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        string $token = null
    ): Response {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw new ResetPasswordNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(
                    ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                    [],
                    'ResetPasswordBundle'
                ),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(
        string $emailFormData,
        EmailService $emailService,
        TranslatorInterface $translator,
        RouterInterface $routerInterface
    ): RedirectResponse {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->redirectToRoute('app_check_email');
        }

        // donne le contexte au router pour generer l'url beta ou prod
        $host = $_ENV["APP_ENV"] == FileService::ENV_DEV
            ? 'aides-terr-php.osc-fr1.scalingo.io'
            : 'aides-territoires.beta.gouv.fr'
        ;
        $context = $routerInterface->getContext();
        $context->setHost($host);
        $context->setScheme('https');

        $emailService->sendEmail(
            $user->getEmail(),
            'Renouvellement de votre mot de passe',
            'user/reset_password/email.html.twig',
            [
                'resetToken' => $resetToken,
            ]
        );

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
