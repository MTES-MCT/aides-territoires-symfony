<?php

namespace App\MessageHandler\Alert;

use App\Entity\Alert\Alert;
use App\Entity\User\User;
use App\Message\Alert\AlertResume;
use App\Repository\Alert\AlertRepository;
use App\Service\Notification\NotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class AlertResumeHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ParamService $paramService,
        private NotificationService $notificationService
    ) {}

    public function __invoke(AlertResume $resume): void
    {
        $today = new \DateTime(date('Y-m-d'));

        // compte les alertes de cette fréquences enovyée aujourd'hui
        /** @var AlertRepository $alertRepository */
        $alertRepository = $this->managerRegistry->getRepository(Alert::class);
        $nbAlerts = $alertRepository->countCustom(
            [
                'alertFrequency' => $resume->getAlertFrequency(),
                'dateLatestAlert' => $today
            ]
        );

        // notif admin
        $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
        $this->notificationService->addNotification(
            $admin,
            'Résume alertes ' . $resume->getAlertFrequency(),
            $nbAlerts . ' alertes ' . $resume->getAlertFrequency() . ' envoyées le ' . $today->format('d/m/Y'),
        );
    }
}
