<?php

namespace App\MessageHandler\Reference;

use App\Entity\Alert\Alert;
use App\Entity\Reference\ProjectReference;
use App\Message\Alert\AlertMessage;
use App\Message\Reference\ProjectReferenceCountAids;
use App\Repository\Alert\AlertRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;

#[AsMessageHandler()]
class ProjectReferenceCountAidsHandler
{
    public function __construct(
        private RouterInterface $routerInterface,
        private ManagerRegistry $managerRegistry,
        private AidService $aidService,
        private AidSearchFormService $aidSearchFormService,
        private ParamService $paramService,
        private EmailService $emailService
    ) {}

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
