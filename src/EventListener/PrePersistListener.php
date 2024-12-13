<?php

namespace App\EventListener;

use App\Entity\Organization\OrganizationInvitation;
use App\Entity\User\User;
use App\EventListener\Organization\OrganizationInvitationListener;
use App\EventListener\User\UserListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;

#[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
class PrePersistListener
{
    public function __construct(
        private UserListener $userListener,
        private OrganizationInvitationListener $organizationInvitationListener,
        private ManagerRegistry $managerRegistry
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        // Position
        if (method_exists($entity, 'getPosition') && !$entity->getPosition()) {
            /** @var ServiceEntityRepository<object> $serviceEntityRepository */
            // @phpstan-ignore-next-line
            $serviceEntityRepository = $this->managerRegistry->getRepository(get_class($entity));
            $entityCount = $serviceEntityRepository->count([]);
            if (method_exists($entity, 'setPosition')) {
                $entity->setPosition($entityCount);
            }
        }

        if ($entity instanceof User) {
            $this->userListener->onPrePersist($args);
        }

        if ($entity instanceof OrganizationInvitation) {
            $this->organizationInvitationListener->onPrePersist($args);
        }
    }
}
