<?php

namespace App\MessageHandler\Backer;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Message\Backer\BackerCountAid;
use App\Repository\Aid\AidRepository;
use App\Repository\Backer\BackerRepository;
use App\Service\Aid\AidService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class BackerCountAidHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AidService $aidService
    ) {
    }

    public function __invoke(BackerCountAid $message): void
    {
        /** @var BackerRepository $backerRepo */
        $backerRepo = $this->managerRegistry->getRepository(Backer::class);

        // charge le porteur
        $backer = $backerRepo->find($message->getIdBacker());

        // met à jour le nombre de porteurs
        if ($backer instanceof Backer) {
            /** @var AidRepository $aidRepo */
            $aidRepo = $this->managerRegistry->getRepository(Aid::class);

            $backer->setNbAids($aidRepo->countCustom(['backer' => $backer]));
            $aidsParams = [
                'backer' => $backer,
                'showInSearch' => true
            ];
            $backer->setAidsLive($this->aidService->searchAids($aidsParams));
            $backer->setNbAidsLive(count($backer->getAidsLive()));
            $backer->setNbAidsLiveFinancial(count($backer->getAidsFinancial()));
            $backer->setNbAidsLiveTechnical(count($backer->getAidsTechnical()));
            $this->managerRegistry->getManager()->persist($backer);
            $this->managerRegistry->getManager()->flush();
        }
    }
}
