<?php

namespace App\MessageHandler\Reference;

use App\Entity\Reference\ProjectReference;
use App\Message\Reference\ProjectReferenceCountAids;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Service\Aid\AidService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class ProjectReferenceCountAidsHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AidService $aidService,
    ) {
    }

    public function __invoke(ProjectReferenceCountAids $message): void
    {
        /** @var ProjectReferenceRepository $projectReferenceRepository */
        $projectReferenceRepository = $this->managerRegistry->getRepository(ProjectReference::class);

        $projectReference = $projectReferenceRepository->find($message->getIdProjectReference());

        if ($projectReference instanceof ProjectReference) {
            // parametres pour requetes aides
            $aidParams = [
                'showInSearch' => true,
                'keyword' => $projectReference->getName(),
                'projectReference' => $projectReference,
            ];

            $projectReference->setNbSearchResult(count($this->aidService->searchAids($aidParams)));
            $this->managerRegistry->getManager()->persist($projectReference);
            $this->managerRegistry->getManager()->flush();
        }
    }
}
