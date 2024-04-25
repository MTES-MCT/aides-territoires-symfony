<?php

namespace App\EventListener\Organization;

use App\Entity\Organization\Organization;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationListener
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
    ) {
        
    }

    public function  onPreRemove(PreRemoveEventArgs $args) : void {
        /** @var Organization $organization */
        $entity = $args->getObject();

        if ($entity->getBacker()) {
            // on regarde si la fiche porteur d'aide n'est pas reliées à d'autres organization
            if (count($entity->getBacker()->getOrganizations()) === 1) {
                $this->managerRegistry->getManager()->remove($entity->getBacker());
            }
        }
    }
}