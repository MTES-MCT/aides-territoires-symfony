<?php

namespace App\Command\Cron\Alert;

use App\Entity\Aid\Aid;
use App\Entity\Alert\Alert;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Service\Aid\AidSearchClass;
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
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'at:cron:alert:send_daily', description: 'Envoi des alertes quotidiennes')]
class AlertSendDailyCommand extends Command
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
        protected RouterInterface $routerInterface,
        protected NotificationService $notificationService
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
            // if ($this->kernelInterface->getEnvironment() != 'prod') {
            //     $io->info('Uniquement en prod');
            //     return Command::FAILURE;
            // }
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
        $alerts = $this->managerRegistry->getRepository(Alert::class)->findToSendDaily();

        // pour le retour
        $nbAlertSend = 0;

        // prépare la date de publication à checker
        $publishedAfter = new \DateTime(date('Y-m-d', strtotime('-1 day')));

        // parametres pour requetes aides
        $aidParams =[
            'showInSearch' => true,
            'publishedAfter' => $publishedAfter,
        ];
        // recupere les nouvelles aides qui correspondent à l'alerte
        $aids = $this->aidService->searchAids($aidParams);
        // les ids de périmètres de chaque aides
        $aidsPerimeterIds = [];
        foreach ($aids as $aid) {
            $aidsPerimeterIds[$aid->getId()] = $this->managerRegistry->getRepository(Perimeter::class)->getIdPerimetersContainedIn([
                'perimeter' => $aid->getPerimeter()
            ]);
        }

        dump('avant filtre', count($aids));
        // pour chaque alerte on regarde si de nouvelles aide (datePublished = hier) correspondent
        /**@var Alert $alert */
        foreach ($alerts as $key => $alert) {
            /** @var AidSearchClass $aidSearchClass */
            $aidSearchClass = $this->aidSearchFormService->getAidSearchClass(
                params: [
                    'querystring' => $alert->getQuerystring(),
                    ]
            );
            $aidParams = $this->aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass);

            // on filtre les aides avec les paramètre de l'alerte
            $alertAids = [];
            foreach ($aids as $aid) {
                if ($this->aidMatchAidSearchClass(
                        $aid,
                        $aidSearchClass,
                        [
                            'aidsPerimeterIds' => $aidsPerimeterIds[$aid->getId()] ?? []
                        ]
                    )
                ) {
                    $alertAids[] = $aid;
                }
            }

            dd('apres filtre', count($alertAids));
            // parametres pour requetes aides
            $aidParams =[
                'showInSearch' => true,
                'publishedAfter' => $publishedAfter,
            ];
            $aidParams = array_merge($aidParams, $this->aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));

            // recupere les nouvelles aides qui correspondent à l'alerte
            $aids = $this->aidService->searchAids($aidParams);

            // il y a de nouvelles aides
            if (count($aids) > 0) {
                if ($alert->getTitle() === $this->paramService->get('addna_alert_title')) {
                    $emailSubjectPrefix = $this->paramService->get('addna_alert_email_subject_prefix');
                } else {
                    $emailSubjectPrefix = $this->paramService->get('email_subject_prefix');
                }
                $today = new \DateTime(date('Y-m-d H:i:s'));
                $emailSubject = $emailSubjectPrefix . ' '. $today->format('d/m/Y') . ' — De nouvelles aides correspondent à vos recherches';
                $subject = count($aids).' résultat'.(count($aids) > 1 ? 's' : '').' pour votre alerte';
                $this->emailService->sendEmail(
                    $alert->getEmail(),
                    $emailSubject,
                    'emails/alert/alert_send.html.twig',
                    [
                        'subject' => $subject,
                        'alert' => $alert,
                        'aids' => $aids,
                        'aidsDisplay' => array_slice($aids, 0, 3)
                    ]
                );

                $alert->setTimeLatestAlert($today);
                $alert->setDateLatestAlert($today);
                $this->managerRegistry->getManager()->persist($alert);
                // sauvegarde
                $this->managerRegistry->getManager()->flush();
                // libère mémoire
                $this->managerRegistry->getManager()->clear();
                // incrémente le compteur
                $nbAlertSend++;
            }

            // libère mémoire
            unset($aids);
            unset($alerts[$key]);
        }

        // notif admin
        $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
        $this->notificationService->addNotification($admin, 'Envoi des alertes quotidiennes', $nbAlertSend. ' alertes envoyées pour les aides publiées après le ' . $publishedAfter->format('d/m/Y') . ' inclus');
        
        // success
        $io->success($nbAlertSend. ' alertes envoyées');
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }

    private function aidMatchAidSearchClass(Aid $aid, AidSearchClass $aidSearchClass, array $params = []): bool
    {
        if ($aidSearchClass->getOrganizationType()) {
            if ($aidSearchClass->getOrganizationType() != $aid->getOrganizationType()) {
                return false;
            }
        }

        if ($aidSearchClass->getSearchPerimeter()) {
            if (!isset($params['aidsPerimeterIds'])) {
                return false;
            }
            if (!in_array($aidSearchClass->getSearchPerimeter()->getId(), $params['aidsPerimeterIds'])) {
                return false;
            }
        }
        return true;
    }

    private function aidMatchKeyword(Aid $aid, string $keyword)
    {
        
    }

}
