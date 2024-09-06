<?php

namespace App\MessageHandler\Perimeter;

use App\Entity\Backer\Backer;
use App\Entity\Perimeter\Perimeter;
use App\Message\Perimeter\CountyCountBacker;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class CountyCountBackerHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function __invoke(CountyCountBacker $message): void
    {
        /** @var PerimeterRepository $perimeterRepo */
        $perimeterRepo = $this->managerRegistry->getRepository(Perimeter::class);

        /** @var BackerRepository $backerRepo */
        $backerRepo = $this->managerRegistry->getRepository(Backer::class);

        // charge le département
        $county = $perimeterRepo->find($message->getIdPerimeter());

        // met à jour le nombre de porteurs
        if ($county instanceof Perimeter) {
            $county->setBackersCount($backerRepo->countBackerWithAidInCounty(['id' => $county->getId()]));
            $this->managerRegistry->getManager()->persist($county);
            $this->managerRegistry->getManager()->flush();
        }
    }
}
