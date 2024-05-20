<?php

namespace App\MessageHandler\Alert;

use App\Entity\Alert\Alert;
use App\Message\Alert\AlertMessage;
use App\Repository\Alert\AlertRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;

#[AsMessageHandler()]
class AlertMessageHandler
{
    public function __construct(
        private RouterInterface $routerInterface,
        private ManagerRegistry $managerRegistry,
        private AidService $aidService,
        private AidSearchFormService $aidSearchFormService,
        private ParamService $paramService,
        private EmailService $emailService
    ) {
    }

    public function __invoke(AlertMessage $message): void
    {
        /** @var AlertRepository $alertRepository */
        $alertRepository = $this->managerRegistry->getRepository(Alert::class);

        $alert = $alertRepository->find($message->getIdAlert());
        if ($alert instanceof Alert) {
            // donne le contexte au router pour generer l'url beta ou prod
            $host = $_ENV["APP_ENV"] == 'dev' ? 'aides-terr-php.osc-fr1.scalingo.io' : 'aides-territoires.beta.gouv.fr';
            $context = $this->routerInterface->getContext();
            $context->setHost($host);
            $context->setScheme('https');

            // prépare les deux dates de publication à checker
            $publishedAfter = new \DateTime(date('Y-m-d', strtotime('-1 day')));

            $aidSearchClass = $this->aidSearchFormService->getAidSearchClass(
                params: [
                    'querystring' => $alert->getQuerystring(),
                    ]
            );

            // parametres pour requetes aides
            $aidParams =[
                'showInSearch' => true,
                'publishedAfter' => $publishedAfter,
                'noRelaunch' => true,
                'noPostPopulate' => true
            ];
            $aidParams = array_merge($aidParams, $this->aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));
            
            // recupere les nouvelles aides qui correspondent à l'alerte
            $aids = $this->aidService->searchAids($aidParams);
            if (!empty($aids)) {
                // il y a de nouvelles aides
                if ($alert->getTitle() === $this->paramService->get('addna_alert_title')) {
                    $emailSubjectPrefix = $this->paramService->get('addna_alert_email_subject_prefix');
                } else {
                    $emailSubjectPrefix = $this->paramService->get('email_subject_prefix');
                }
                $today = new \DateTime(date('Y-m-d H:i:s'));
                $emailSubject = $emailSubjectPrefix . ' '. $today->format('d/m/Y') . ' — De nouvelles aides correspondent à vos recherches';
                $subject = count($aids).' résultat'.(count($aids) > 1 ? 's' : '').' pour votre alerte';

                // Force le tri par date de publication DESC
                parse_str($alert->getQuerystring(), $params);
                $params['orderBy'] = 'publication_date';
                $querystringOrdered = http_build_query($params);

                // email
                $this->emailService->sendEmail(
                    $alert->getEmail(),
                    $emailSubject,
                    'emails/alert/alert_send.html.twig',
                    [
                        'subject' => $subject,
                        'alert' => $alert,
                        'aids' => $aids,
                        'aidsDisplay' => array_slice($aids, 0, 3),
                        'querystringOrdered' => $querystringOrdered
                    ]
                );

                $alert->setTimeLatestAlert($today);
                $alert->setDateLatestAlert($today);
                $this->managerRegistry->getManager()->persist($alert);

                // sauvegarde
                $this->managerRegistry->getManager()->flush();
            }
        }
    }
}
