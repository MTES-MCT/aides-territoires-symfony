<?php

namespace App\MessageHandler\Perimeter;

use App\Entity\Perimeter\Perimeter;
use App\Message\Perimeter\MsgPerimeterCombine;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class MsgPerimeterCombineHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {}

    public function __invoke(MsgPerimeterCombine $message): void
    {
        /** @var PerimeterRepository $perimeterRepo */
        $perimeterRepo = $this->managerRegistry->getRepository(Perimeter::class);

        $perimeter = $perimeterRepo->find($message->getIdPerimeter());

        $perimeterToAdd = $perimeterRepo->find($message->getIdPerimeterToAdd());

        $perimeter->addPerimetersFrom($perimeterToAdd);
        // ajoute les enfants
        foreach ($perimeterToAdd->getPerimetersFrom() as $perimeterFrom) {
            $perimeter->addPerimetersFrom($perimeterFrom);
        }
        // ajoute les parents
        foreach ($perimeterToAdd->getPerimetersTo() as $perimeterTo) {
            $perimeter->addPerimetersTo($perimeterTo);
        }

        // sauvegarde
        $this->managerRegistry->getManager()->persist($perimeter);
        $this->managerRegistry->getManager()->flush();
    }
}
