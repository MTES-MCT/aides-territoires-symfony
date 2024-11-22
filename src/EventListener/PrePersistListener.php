<?php

namespace App\EventListener;

use App\Entity\Organization\OrganizationInvitation;
use App\Entity\User\User;
use App\EventListener\Organization\OrganizationInvitationListener;
use App\EventListener\User\UserListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
class PrePersistListener
{
    public function __construct(
        protected EntityManagerInterface $em,
        private UserListener $userListener,
        private OrganizationInvitationListener $organizationInvitationListener
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        // Position
        if (method_exists($entity, 'getPosition') && !$entity->getPosition()) {
            $entityCount = $this->em->getRepository(get_class($entity))->count([]); // compte les entites
            $entity->setPosition($entityCount);
        }

        if ($entity instanceof User) {
            $this->userListener->onPrePersist($args);
        }

        if ($entity instanceof OrganizationInvitation) {
            $this->organizationInvitationListener->onPrePersist($args);
        }
    }
}
