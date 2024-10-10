<?php

namespace App\EventListener\Organization;

use App\Entity\Organization\OrganizationInvitation;
use Doctrine\ORM\Event\PrePersistEventArgs;

class OrganizationInvitationListener
{
    public function onPrePersist(PrePersistEventArgs $args): void
    {
        /** @var OrganizationInvitation $entity */
        $entity = $args->getObject();

        // on force les minuscules sur l'email
        $entity->setEmail(strtolower($entity->getEmail()));
    }
}
