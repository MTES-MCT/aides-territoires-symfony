<?php

namespace App\MessageHandler\User;

use App\Message\User\MsgAidStatsSpreadsheetOfUser;
use App\Repository\Aid\AidRepository;
use App\Repository\User\UserRepository;
use App\Service\Aid\AidProjectService;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use App\Service\File\FileService;
use App\Service\Log\LogAidApplicationUrlClickService;
use App\Service\Log\LogAidOriginUrlClickService;
use App\Service\Log\LogAidViewService;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\RouterInterface;

#[AsMessageHandler()]
class MsgAidStatsSpreadsheetOfUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private AidRepository $aidRepository,
        private AidService $aidService,
        private StringService $stringService,
        private LogAidViewService $logAidViewService,
        private LogAidApplicationUrlClickService $logAidApplicationUrlClickService,
        private LogAidOriginUrlClickService $logAidOriginUrlClickService,
        private AidProjectService $aidProjectService,
        private EmailService $emailService,
        private FileService $fileService,
        private ParamService $paramService,
        private NotificationService $notificationService,
        private RouterInterface $routerInterface,
    ) {
    }

    public function __invoke(MsgAidStatsSpreadsheetOfUser $message): void
    {
        try {
            $user = $this->userRepository->find($message->getIdUser());
            $dateMin = $message->getDateMin();
            $dateMax = $message->getDateMax();

            // donne le contexte au router pour generer l'url beta ou prod
            $host = $_ENV["APP_ENV"] == 'dev' ? 'aides-terr-php.osc-fr1.scalingo.io' : 'aides-territoires.beta.gouv.fr';
            $context = $this->routerInterface->getContext();
            $context->setHost($host);
            $context->setScheme('https');
            
            $spreadsheet = $this->aidService->getAidStatsSpreadSheetOfUser(
                $user,
                $dateMin,
                $dateMax,
                $this->aidRepository,
                $this->stringService,
                $this->logAidViewService,
                $this->logAidApplicationUrlClickService,
                $this->logAidOriginUrlClickService,
                $this->aidProjectService,
            );

            // Génération du fichier Excel
            $writer = new Xlsx($spreadsheet);

            // nom de fichier
            $filename =
                'AT_statistiques_aides_'
                . $dateMin->format('d_m_Y')
                . '_au_' . $dateMax->format('d_m_Y')
                . '.xlsx';

            // dossier temp
            $tmpFolder = $this->fileService->getUploadTmpDir();
            if (!is_dir($tmpFolder)) {
                mkdir($tmpFolder, 0777, true);
            }

            // cible
            $fileTarget = $tmpFolder . '/' . $filename;

            // Ecriture du fichier
            $writer->save($fileTarget);

            // Envoi l'email
            $send = $this->emailService->sendEmail(
                $message->getForceEmail() ? $message->getForceEmail() : $user->getEmail(),
                $message->getForceSubject() ? $message->getForceSubject() : 'Export des statistiques de vos aides',
                'emails/base.html.twig',
                [
                    'subject' => $message->getForceSubject()
                        ? $message->getForceSubject()
                        : 'Export des statistiques de vos aides',
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

            if (!$send) {
                throw new \Exception('Erreur lors de l\'envoi de l\'email');
            }
        } catch (\Exception $e) {
            // notif admin erreur
            $admin = $this->userRepository->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Erreur lors de l\'export PDF des statistiques des aides par ' .
                    (isset($user) ? $user->getEmail() : 'inconnu'),
                $e->getMessage()
            );
        }
    }
}
