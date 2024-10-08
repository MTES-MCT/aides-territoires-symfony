<?php

namespace App\MessageHandler\User;

use App\Entity\Project\Project;
use App\Entity\User\User;
use App\Message\User\MsgProjectExportAids;
use App\Repository\Project\ProjectRepository;
use App\Repository\User\UserRepository;
use App\Security\Voter\User\UserProjectAidsVoter;
use App\Service\Email\EmailService;
use App\Service\Export\SpreadsheetExporterService;
use App\Service\File\FileService;
use App\Service\Notification\NotificationService;
use App\Service\Project\ProjectService;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

#[AsMessageHandler()]
class MsgProjectExportAidsHandler
{
    public function __construct(
        private NotificationService $notificationService,
        private ManagerRegistry $managerRegistry,
        private ParamService $paramService,
        private StringService $stringService,
        private Environment $twig,
        private EmailService $emailService,
        private FileService $fileService,
        private AuthorizationCheckerInterface $authorizationChecker,
        private LoggerInterface $loggerInterface,
        private SpreadsheetExporterService $spreadsheetExporterService,
        private ProjectService $projectService
    ) {
    }

    public function __invoke(MsgProjectExportAids $message): void
    {
        try {
            /** @var UserRepository $userRepository */
            $userRepository = $this->managerRegistry->getRepository(User::class);
            $user = $userRepository->find($message->getIdUser());
            if (!$user instanceof User) {
                throw new \Exception('Utilisateur non trouvé');
            }

            /** @var ProjectRepository $projectRepository */
            $projectRepository = $this->managerRegistry->getRepository(Project::class);

            // le projet
            $project = $projectRepository->find($message->getIdProject());
            if (!$project instanceof Project) {
                throw new \Exception('Projet non trouvé');
            }

            // vérification accès
            if (!$this->authorizationChecker->isGranted(UserProjectAidsVoter::IDENTIFIER, ['user' => $user, 'project' => $project])) {
                throw new \Exception(UserProjectAidsVoter::MESSAGE_ERROR);
            }

            switch ($message->getFormat()) {
                case FileService::FORMAT_PDF:
                    $this->exportAidsPdf($user, $project);
                    break;
                case FileService::FORMAT_CSV:
                    $this->exportAidsCsv($user, $project);
                    break;
                case FileService::FORMAT_XLSX:
                    $this->exportAidsXslx($user, $project);
                    break;
                default:
                    throw new \Exception('Format non géré');
            }
            
        } catch (\Exception $e) {
            dd($e->getMessage());
            // notif erreur
            $this->loggerInterface->error('Erreur dans export aides du projet', [
                'exception' => $e,
                'idUser' => $user->getId(),
                'idProject' => $project->getId(),
            ]);
        }
    }

    private function exportAidsPdf(User $user, Project $project): void
    {
        $now = new \DateTime(date('Y-m-d H:i:s'));
        $filename = 'Aides-territoires_-_' . $now->format('Y-m-d') . '_-_' . $project->getSlug() . '.pdf';

        // Récupérez le contenu du PDF
        $pdfContent = $this->projectService->getProjectAidsExportPdfContent($project);

        // cible
        $fileTarget = $this->getFileTarget($filename);

        // Enregistre le fichier PDF dans le dossier temporaire
        if (!file_put_contents($fileTarget, $pdfContent)) {
            throw new \Exception('Erreur lors de la création du fichier PDF');
        }
        
        // Envoi l'email
        $send = $this->sendExportByEmail($user, $fileTarget);

        // Supprime le fichier
        @unlink($fileTarget);
        
        if (!$send) {
            throw new \Exception('Erreur lors de l\'envoi de l\'email');
        }
    }

    private function exportAidsCsv(User $user, Project $project): void
    {
        // Création du tableur
        $spreadsheet = $this->spreadsheetExporterService->getProjectAidsSpreadsheet($project);
        $format = FileService::FORMAT_CSV;

        // Passage au format CSV
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(';');
        $writer->setEnclosure('"');
        
        // nom de fichier
        $now = new \DateTime(date(SpreadsheetExporterService::TODAY_DATE_FORMAT));
        $filename = 'Aides-territoires_-_' . $now->format('Y-m-d') . '_-_' . $project->getSlug().'.'.$format;

        // cible
        $fileTarget = $this->getFileTarget($filename);

        // Ecriture du fichier
        $writer->save($fileTarget);

        // Envoi l'email
        $send = $this->sendExportByEmail($user, $fileTarget);

        // Supprime le fichier
        @unlink($fileTarget);
        
        if (!$send) {
            throw new \Exception('Erreur lors de l\'envoi de l\'email');
        }
    }

    private function exportAidsXslx(User $user, Project $project): void
    {
        // Création du tableur
        $spreadsheet = $this->spreadsheetExporterService->getProjectAidsSpreadsheet($project);
        $format = FileService::FORMAT_XLSX;

        // Passage au format CSV
        $writer = new Xlsx($spreadsheet);
        
        // nom de fichier
        $now = new \DateTime(date(SpreadsheetExporterService::TODAY_DATE_FORMAT));
        $filename = 'Aides-territoires_-_' . $now->format('Y-m-d') . '_-_' . $project->getSlug().'.'.$format;

        // cible
        $fileTarget = $this->getFileTarget($filename);

        // Ecriture du fichier
        $writer->save($fileTarget);

        // Envoi l'email
        $send = $this->sendExportByEmail($user, $fileTarget);

        // Supprime le fichier
        @unlink($fileTarget);
        
        if (!$send) {
            throw new \Exception('Erreur lors de l\'envoi de l\'email');
        }
    }

    private function getFileTarget(string $filename): string
    {
        // dossier temp
        $tmpFolder = $this->fileService->getUploadTmpDir();
        if (!is_dir($tmpFolder)) {
            mkdir($tmpFolder, 0777, true);
        }
        
        // cible
        return $tmpFolder . '/' . $filename;
    }

    private function sendExportByEmail(User $user, string $fileTarget): bool
    {
        return $this->emailService->sendEmail(
            $user->getEmail(),
            'Export des aides de votre projet',
            'emails/base.html.twig',
            [
                'subject' => 'Export des aides de votre projet',
                'body' => 'Votre export en pièce jointe',
            ],
            [
                'attachments' => [
                    $fileTarget
                ]
            ]
        );
    }
}
