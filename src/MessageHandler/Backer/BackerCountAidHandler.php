<?php

namespace App\MessageHandler\Backer;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Message\Backer\BackerCountAid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class BackerCountAidHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
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
            $backer->setNbAidsLive($aidRepo->countCustom(['backer' => $backer, 'showInSearch' => true]));
            $this->managerRegistry->getManager()->persist($backer);
            $this->managerRegistry->getManager()->flush();
        }
    }
}
