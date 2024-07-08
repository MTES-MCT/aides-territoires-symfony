<?php

namespace App\MessageHandler\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidInstructor;
use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\KeywordReferenceSuggested;
use App\Message\Aid\AidExtractKeyword;
use App\Message\Aid\AidPropagateUpdate;
use App\Repository\Aid\AidRepository;
use App\Service\Aid\AidService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class AidPropagateUpdateHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function __invoke(AidPropagateUpdate $message): void
    {
        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        $aidGeneric = $aidRepository->find($message->getIdAidGeneric());
        $aidLocal = $aidRepository->find($message->getIdAidLocal());

        
        foreach ($aidGeneric->getSanctuarizedFields() as $sanctuarizedField) {
            if ($sanctuarizedField->getName() == 'aidFinancers') {
                foreach ($aidLocal->getAidFinancers() as $aidFinancer) {
                    $aidLocal->removeAidFinancer($aidFinancer);
                }
                foreach ($aidGeneric->getAidFinancers() as $aidFinancer) {
                    $newAidFinancer = new AidFinancer();
                    $newAidFinancer->setBacker($aidFinancer->getBacker());
                    $aidLocal->addAidFinancer($newAidFinancer);
                }
            } elseif ($sanctuarizedField->getName() == 'aidInstructors') {
                foreach ($aidLocal->getAidInstructors() as $aidInstructor) {
                    $aidLocal->removeAidInstructor($aidInstructor);
                }
                foreach ($aidGeneric->getAidInstructors() as $aidInstructor) {
                    $newAidInstructor = new AidInstructor();
                    $newAidInstructor->setBacker($aidInstructor->getBacker());
                    $aidLocal->addAidInstructor($newAidInstructor);
                }
            } else {
                if (
                    method_exists($aidLocal, 'set' . ucfirst($sanctuarizedField->getName()))
                    && method_exists($aidGeneric, 'get' . ucfirst($sanctuarizedField->getName()))
                ) {
                    $aidLocal->{'set' . ucfirst($sanctuarizedField->getName())}($aidGeneric->{'get' . ucfirst($sanctuarizedField->getName())}());
                } elseif (
                    method_exists($aidLocal, 'set' . ucfirst($sanctuarizedField->getName()))
                    && method_exists($aidGeneric, 'is' . ucfirst($sanctuarizedField->getName()))
                ) {
                    $aidLocal->{'set' . ucfirst($sanctuarizedField->getName())}($aidGeneric->{'is' . ucfirst($sanctuarizedField->getName())}());
                }
            }
        }

        $this->managerRegistry->getManager()->persist($aidLocal);
        $this->managerRegistry->getManager()->flush();
    }
}
