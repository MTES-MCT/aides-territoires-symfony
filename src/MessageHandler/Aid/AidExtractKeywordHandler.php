<?php

namespace App\MessageHandler\Aid;

use App\Entity\Aid\Aid;
use App\Entity\User\User;
use App\Message\Aid\AidExtractKeyword;
use App\Message\SendNotification;
use App\Repository\Aid\AidRepository;
use App\Service\Aid\AidService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class AidExtractKeywordHandler
{
    public function __construct(
        private NotificationService $notificationService,
        private ManagerRegistry $managerRegistry,
        private ParamService $paramService,
        private AidService $aidService
    ) {
    }

    public function __invoke(AidExtractKeyword $message)
    {
        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        $aid = $aidRepository->find($message->getIdAid());
        if ($aid instanceof Aid) {

        }
    }
}
