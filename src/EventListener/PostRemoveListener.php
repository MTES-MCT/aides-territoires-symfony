<?php

namespace App\EventListener;

use App\Entity\Organization\OrganizationAccess;
use App\EventListener\Organization\OrganizationAccessListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postRemove, priority: 500, connection: 'default')]
class PreRemoveListener
{
    public function __construct(
        protected OrganizationAccessListener $organizationAccessListener,
    ) {}

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof OrganizationAccess) {
            $this->organizationAccessListener->onPostRemove($args);
        }
    }
}