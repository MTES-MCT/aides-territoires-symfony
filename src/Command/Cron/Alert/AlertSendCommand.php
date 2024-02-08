<?php

namespace App\Command\Cron\Alert;

use App\Entity\Aid\Aid;
use App\Entity\Alert\Alert;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Perimeter;
use App\Entity\Backer;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use App\Service\Various\ParamService;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'at:cron:alert:send', description: 'Envoi des alertes')]
class AlertSendCommand extends Command
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
        ini_set('max_execution_time', 60*60*60);
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

        // donne le contexte au router pour generer l'url beta ou prod
        $host = $_ENV["APP_ENV"] == 'dev' ? 'aides-terr-php.osc-fr1.scalingo.io' : 'aides-territoires.beta.gouv.fr';
        $context = $this->routerInterface->getContext();
        $context->setHost($host);
        $context->setScheme('https');
        
        // charge les alertes
        $alerts = $this->managerRegistry->getRepository(Alert::class)->findToSend();

        // pour le retour
        $nbAlertSend = 0;

        // pour chaque alerte on regarde si de nouvelles aide (datePublished = hier) correspondent
        /**@var Alert $alert */
        foreach ($alerts as $alert) {
            $publishedAfter = 
                $alert->getAlertFrequency() === Alert::FREQUENCY_DAILY_SLUG
                    ? new \DateTime(date('Y-m-d', strtotime('-1 day')))
                    : new \DateTime(date('Y-m-d', strtotime('-7 day')))
            ;

            $aidSearchClass = $this->aidSearchFormService->getAidSearchClass(
                params: [
                    'querystring' => $alert->getQuerystring(),
                    ]
            );

            // parametres pour requetes aides
            $aidParams =[
                'showInSearch' => true,
                'publishedAfter' => $publishedAfter
            ];
            $aidParams = array_merge($aidParams, $this->aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));

            // recupere les nouvelles aides qui correspondent à l'alerte
            $aids = $this->managerRegistry->getRepository(Aid::class)->findCustom($aidParams);

            // il y a de nouvelles aides
            if (count($aids) > 0) {
                if ($alert->getTitle() === $this->paramService->get('addna_alert_title')) {
                    $emailSubjectPrefix = $this->paramService->get('addna_alert_email_subject_prefix');
                } else {
                    $emailSubjectPrefix = $this->paramService->get('email_subject_prefix');
                }
                $today = new \DateTime(date('Y-m-d H:i:s'));
                $emailSubject = $emailSubjectPrefix . $today->format('d/m/Y') . ' — De nouvelles aides correspondent à vos recherches';
                
                $this->emailService->sendEmail(
                    $alert->getEmail(),
                    $emailSubject,
                    'emails/alert/alert_send.html.twig',
                    [
                        'subject' => count($aids).' résultats pour votre alerte',
                        'alert' => $alert,
                        'aids' => $aids,
                        'aidsDisplay' => array_slice($aids, 0, 3)
                    ]
                );

                $alert->setTimeLatestAlert($today);
                $alert->setDateLatestAlert($today);
                $this->managerRegistry->getManager()->persist($alert);

                $nbAlertSend++;
            }
        }

        // sauvegarde
        $this->managerRegistry->getManager()->flush();


        // success
        $io->success($nbAlertSend. ' alertes envoyées');
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }

}
