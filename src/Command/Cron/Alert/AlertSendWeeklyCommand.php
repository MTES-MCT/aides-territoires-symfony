<?php

namespace App\Command\Cron\Alert;

use App\Entity\Alert\Alert;
use App\Entity\User\User;
use App\Message\Alert\AlertMessage;
use App\Message\Alert\AlertResume;
use App\Repository\Alert\AlertRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'at:cron:alert:send_weekly', description: 'Envoi des alertes hedbomadaires')]
class AlertSendWeeklyCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Cron';
    protected string $commandTextEnd = '>Cron';

    public function __construct(
        private KernelInterface $kernelInterface,
        private ManagerRegistry $managerRegistry,
        private AidService $aidService,
        private AidSearchFormService $aidSearchFormService,
        private EmailService $emailService,
        private ParamService $paramService,
        private RouterInterface $routerInterface,
        private NotificationService $notificationService,
        private MessageBusInterface $bus
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
            if ($this->kernelInterface->getEnvironment() != 'prod') {
                $io->info('Uniquement en prod');
                return Command::FAILURE;
            }
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
        $timeStart = microtime(true);

        $io = new SymfonyStyle($input, $output);

        /** @var AlertRepository $alertRepo */
        $alertRepo = $this->managerRegistry->getRepository(Alert::class);

        // charge les alertes
        $alerts = $alertRepo->findToSendWeekly();

        // pour le retour
        $nbAlertTotal = count($alerts);

        // prépare la date de publication à checker
        $today = new \DateTime(date('Y-m-d'));
        $weekNumber = $today->format('W');
        $year = $today->format('o');

        $startOfWeek = new \DateTime();
        $startOfWeek->setISODate($year, $weekNumber, 1);

        $publishedAfter = clone $startOfWeek;
        $publishedAfter->modify('-7 days');

        // envoi les alertes dans la file d'attente
        /**@var Alert $alert */
        foreach ($alerts as $alert) {
            $this->bus->dispatch(new AlertMessage($alert->getId()));
        }

        // notif admin
        $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
        $this->notificationService->addNotification($admin, 'Envoi des alertes hebdomadaires', $nbAlertTotal . ' alertes envoyées pour vérification des aides publiées après le ' . $publishedAfter->format('d/m/Y') . ' inclus');

        // on ajoute le resume à la file d'attente
        $this->bus->dispatch(new AlertResume(Alert::FREQUENCY_WEEKLY_SLUG));

        // le temps passé
        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        // success
        $io->success('Temps écoulé : ' . gmdate("H:i:s", $timeEnd) . ' (' . gmdate("H:i:s", intval($time)) . ')');
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }
}
