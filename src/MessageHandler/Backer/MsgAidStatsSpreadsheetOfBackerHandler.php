<?php

namespace App\MessageHandler\Backer;

use App\Message\Backer\MsgAidStatsSpreadsheetOfBacker;
use App\Repository\Aid\AidRepository;
use App\Repository\Backer\BackerRepository;
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

#[AsMessageHandler()]
class MsgAidStatsSpreadsheetOfBackerHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private BackerRepository $backerRepository,
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
        private NotificationService $notificationService
    ) {
    }

    public function __invoke(MsgAidStatsSpreadsheetOfBacker $message): void
    {
        try {
            $backer = $this->backerRepository->find($message->getIdBacker());
            $dateMin = $message->getDateMin();
            $dateMax = $message->getDateMax();

            $spreadsheet = $this->aidService->getAidStatsSpreadSheetOfBacker(
                $backer,
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
                $message->getTargetEmail(),
                'Export des statistiques des aides du porteur ' . $backer->getName(),
                'emails/base.html.twig',
                [
                    'subject' => 'Export des statistiques des aides du porteur ' . $backer->getName(),
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
                'Erreur lors de l\'export PDF des statistiques des aides du porteur '
                    . (isset($backer) ? $backer->getName() : 'inconnu'),
                $e->getMessage()
            );
        }
    }
}
