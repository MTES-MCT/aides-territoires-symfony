<?php

namespace App\EventListener;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationAccess;
use App\Entity\User\User;
use App\EventListener\Organization\OrganizationAccessListener;
use App\EventListener\Organization\OrganizationListener;
use App\EventListener\User\UserListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preRemove, priority: 500, connection: 'default')]
class PreRemoveListener
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserListener $userListener,
        protected OrganizationAccessListener $organizationAccessListener,
        protected OrganizationListener $organizationListener
    ) {}

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        
        if ($entity instanceof User) {
            $this->userListener->onPreRemove($args);
        }

        if ($entity instanceof OrganizationAccess) {
            $this->organizationAccessListener->onPreRemove($args);
        }

        if ($entity instanceof Organization) {
            $this->organizationListener->onPreRemove($args);
        }
    }
}