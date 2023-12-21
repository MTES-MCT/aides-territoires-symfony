<?php

namespace App\Controller\Admin\Security;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class QrCodeController extends AbstractController
{
    #[Route(
        '/admin/user/qrcode/{idUser}/',
        name: 'app_admin_qr_code_ga',
        requirements: ['idUser' => '[0-9]+']
    )]
    public function displayTotpQrCode(
        $idUser,
        TotpAuthenticatorInterface $totpAuthenticator,
        ManagerRegistry $managerRegistry,
        UserRepository $userRepository,
        UserService $userService
        ): Response
    {
        /** @var User $user */
        $user = $userRepository->find($idUser);
        if (!$userService->isUserGranted($user, User::ROLE_ADMIN)) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        if (!$user->getToptpSecret()) {
            $user->setTotpSecret($totpAuthenticator->generateSecret());
            $managerRegistry->getManager()->persist($user);
            $managerRegistry->getManager()->flush();
        }

        return $this->displayQrCode($totpAuthenticator->getQRContent($user));
    }

    private function displayQrCode(string $qrCodeContent): Response
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrCodeContent)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(200)
            ->margin(0)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }
}