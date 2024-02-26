<?php

namespace App\Command\Cron\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User\Notification;
use App\Entity\User\User;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use App\Service\Various\ParamService;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'at:cron:notification:send_daily', description: 'Envoi des notifications quotidiennes')]
class NotificationSendDailyCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Cron';
    protected string $commandTextEnd = '>Cron';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry,
        protected AidService $aidService,
        protected AidSearchFormService $aidSearchFormService,
        protected EmailService $emailService,
        protected ParamService $paramService,
        protected RouterInterface $routerInterface
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function configure() : void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try  {
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

        // charge les utilisateurs avec des notificaitons non envoyées
        $users = $this->managerRegistry->getRepository(User::class)->findWithUnsentNotification(['notificationEmailFrequency' => User::NOTIFICATION_DAILY]);

        // pour le retour
        $nbOk = 0;
        $nbError = 0;

        // donne le contexte au router pour generer l'url beta ou prod
        $host = $_ENV["APP_ENV"] == 'dev' ? 'aides-terr-php.osc-fr1.scalingo.io' : 'aides-territoires.beta.gouv.fr';
        $context = $this->routerInterface->getContext();
        $context->setHost($host);
        $context->setScheme('https');
        
        // Pour chaque utilisateurs on lui envoi un email avec toutes les notifications non envoyées
        foreach ($users as $user) {
            $notifications = $this->managerRegistry->getRepository(Notification::class)->findToSend(['user' => $user]);
            if (count($notifications) > 0) {
                $subject = (count($notifications) > 1) 
                    ? 'Vous avez des notifications non lues'
                    : 'Vous avez une notification non lue';

                $send = $this->emailService->sendEmail(
                    $user->getEmail(),
                    $subject,
                    'emails/user/unread_notification.html.twig',
                    [
                        'user' => $user,
                        'nbNotifications' => count($notifications)
                    ]
                );

                if ($send) {
                    $nbOk++;
                    $now = new \DateTime(date('Y-m-d H:i:s'));
                    foreach ($notifications as $notification) {
                        $notification->setTimeEmail($now);
                        $this->managerRegistry->getManager()->persist($notification);
                    }
                    $this->managerRegistry->getManager()->flush();
                } else {
                    $nbError++;
                }
            }
        }

        
        // success
        $io->success(count($users). ' utilisateurs, ' . $nbOk . ' notifications envoyées, ' . $nbError . ' erreurs');
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }
}
