<?php

namespace App\MessageHandler\User;

use App\Entity\Aid\Aid;
use App\Entity\User\User;
use App\Message\User\AidsExportPdf;
use App\Repository\Aid\AidRepository;
use App\Repository\User\UserRepository;
use App\Service\Email\EmailService;
use App\Service\File\FileService;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;

#[AsMessageHandler()]
class AidExportPdfHandler
{
    public function __construct(
        private NotificationService $notificationService,
        private ManagerRegistry $managerRegistry,
        private ParamService $paramService,
        private StringService $stringService,
        private Environment $twig,
        private EmailService $emailService,
        private FileService $fileService
    ) {
    }

    public function __invoke(AidsExportPdf $message): void
    {
        try {
            /** @var UserRepository $userRepository */
            $userRepository = $this->managerRegistry->getRepository(User::class);
            $user = $userRepository->find($message->getIdUser());
            if (!$user instanceof User) {
                return;
            }

            /** @var AidRepository $aidRepository */
            $aidRepository = $this->managerRegistry->getRepository(Aid::class);

            // les aides
            $aids = $aidRepository->findCustom(
                [
                    'author' => $user,
                    'showInSearch' => true
                ]
            );

            // nom de fichier
            $today = new \DateTime(date('Y-m-d H:i:s'));
            $organizationName = $user->getDefaultOrganization() ? $this->stringService->getSLug($user->getDefaultOrganization()->getName()) : '';
            $filename = 'Aides-territoires-'.$today->format('d_m_Y').'-'.$organizationName;

            // créer le PDF
            $pdfOptions = new Options();
            $pdfOptions->setIsRemoteEnabled(true);

            // instantiate and use the dompdf class
            $dompdf = new Dompdf($pdfOptions);

            $dompdf->loadHtml(
                $this->twig->render('user/aid/aids_export_pdf.html.twig', [
                    'aids' => $aids,
                    'organization' => $user->getDefaultOrganization() ?? null
                ])
            );

            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('A4', 'portrait');

            // Render the HTML as PDF
            $dompdf->render();

            $tmpFolder = $this->fileService->getUploadTmpDir();
            if (!is_dir($tmpFolder)) {
                mkdir($tmpFolder, 0777, true);
            }

            $fileTarget = $tmpFolder.$filename.'.pdf';
            // Enregistre le fichier PDF dans le dossier temporaire
            file_put_contents($fileTarget, $dompdf->output());

            // Envoi l'email
            $this->emailService->sendEmail(
                $user->getEmail(),
                'Export des aides',
                'emails/base.html.twig',
                [
                    'subject' => 'Export des aides',
                    'body' => 'Votre export en pièce jointe',
                ],
                [
                    'attachments' => [
                        $fileTarget
                    ]
                ]
            );

            // Supprime le fichier
            @unlink($fileTarget);

            $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Export PDF des aides',
                'fini ok'
            );

        } catch (\Exception $e) {
            $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Erreur lors de l\'export PDF des aides',
                $e->getMessage()
            );
        }
    }
}
