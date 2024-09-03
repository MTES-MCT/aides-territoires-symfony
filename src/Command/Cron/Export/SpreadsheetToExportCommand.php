<?php

namespace App\Command\Cron\Export;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Cron\CronExportSpreadsheet;
use App\Repository\Cron\CronExportSpreadsheetRepository;
use App\Service\Email\EmailService;
use App\Service\Export\SpreadsheetExporterService;
use App\Service\Various\ParamService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'at:cron:export:spreadsheet_export', description: 'Envoi des exports trop volumineux')]
class SpreadsheetToExportCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Cron';
    protected string $commandTextEnd = '>Cron';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry,
        protected EntityManagerInterface $entityManager,
        protected EmailService $emailService,
        protected ParamService $paramService,
        protected SpreadsheetExporterService $spreadsheetExporterService,
        protected RouterInterface $routerInterface
    ) {
        ini_set('max_execution_time', 60 * 60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try {
            // generate menu
            $this->cronTask($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function cronTask($input, $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var CronExportSpreadsheetRepository $cronExportSpreadsheetRepo */
        $cronExportSpreadsheetRepo = $this->managerRegistry->getRepository(CronExportSpreadsheet::class);

        // charge le dernier export à traiter
        /** @var CronExportSpreadsheet $cronExportSpreadsheet */
        $cronExportSpreadsheet = $cronExportSpreadsheetRepo->findOneToExport();
        if ($cronExportSpreadsheet === null) {
            $io->success('Aucun export à traiter');
            return;
        }

        // si il est déjà en cours on attends
        if ($cronExportSpreadsheet->isProcessing() === true) {
            $io->success('Export déjà en cours de traitement');
            return;
        }

        try {
            // donne le contexte au router pour generer l'url beta ou prod
            $host = 'aides-territoires.beta.gouv.fr';
            $context = $this->routerInterface->getContext();
            $context->setHost($host);
            $context->setScheme('https');

            // passe en cours de traitement pour éviter de le relancer
            $cronExportSpreadsheet->setProcessing(true);
            $this->managerRegistry->getManager()->persist($cronExportSpreadsheet);
            $this->managerRegistry->getManager()->flush();

            // la requete sql
            $sqlParams = [];
            if ($cronExportSpreadsheet->getSqlParams() !== null) {
                foreach ($cronExportSpreadsheet->getSqlParams() as $param) {
                    if (isset($param['name']) && isset($param['value'])) {
                        $sqlParams[$param['name']] = $param['value'];
                        if (isset($param['value']['date'])) {
                            $sqlParams[$param['name']] = new \DateTime($param['value']['date']);
                        }
                    }
                }
            }

            $query = $this->entityManager
                ->createQuery($cronExportSpreadsheet->getSqlRequest())
                ->setParameters($sqlParams);

            // le fichier d'export
            $fileTarget = $this->spreadsheetExporterService->exportToFile(
                results: $query->getResult(),
                entityFqcn: $cronExportSpreadsheet->getEntityFqcn(),
                filename: $cronExportSpreadsheet->getFilename(),
                format: $cronExportSpreadsheet->getFormat(),
            );

            // libère la mémoire
            unset($results);

            // envoi de l'email
            $this->emailService->sendEmail(
                $cronExportSpreadsheet->getUser()->getEmail(),
                'Votre export est prêt',
                'emails/cron/export/export_send.html.twig',
                [],
                [
                    'attachments' => [$fileTarget]
                ]
            );

            // supprime le fichier
            @unlink($fileTarget);

            // passe le statut à envoyé
            $cronExportSpreadsheet->setTimeEmail(new \DateTime(date('Y-m-d H:i:s')));
            $cronExportSpreadsheet->setProcessing(false);

            // sauvegarde
            $this->managerRegistry->getManager()->persist($cronExportSpreadsheet);
            $this->managerRegistry->getManager()->flush();


            // success
            $io->success('export traité');
            $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
        } catch (\Exception $e) {
            // passe en erreur pour éviter de le relancer
            $cronExportSpreadsheet->setError(true);
            $cronExportSpreadsheet->setProcessing(false);
            $this->managerRegistry->getManager()->persist($cronExportSpreadsheet);
            $this->managerRegistry->getManager()->flush();

            $io->error($e->getMessage());
        }
    }
}
