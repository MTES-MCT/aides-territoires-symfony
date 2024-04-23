<?php

namespace App\EventListener\Organization;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationAccess;
use App\Entity\Organization\OrganizationInvitation;
use App\Service\Organization\OrganizationService;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationAccessListener
{
    public function __construct(
        protected OrganizationService $organizationService,
        protected ManagerRegistry $managerRegistry
    ) {
        
    }


    public function onPreRemove(PreRemoveEventArgs $args): void {
        /** @var OrganizationAccess $entity */
        $entity = $args->getObject();

        // supprimes les invitation également
        $email = $entity->getUser() ? $entity->getUser()->getEmail() : null;
        if ($email && $entity->getOrganization()) {
            $organizationInvitations = $this->managerRegistry->getRepository(OrganizationInvitation::class)->findBy([
                'email' => $email,
                'organization' => $entity->getOrganization()
            ]);
            foreach ($organizationInvitations as $organizationInvitation) {
                $this->managerRegistry->getManager()->remove($organizationInvitation);
            }   
        }

        // notifie les autres membtres
        if ($entity->getOrganization() instanceof Organization) {
            $this->organizationService->sendNotificationToMembers(
                $entity->getOrganization(),
                'Départ d\'un membre',
                $entity->getUser()->getFirstname().' '.$entity->getUser()->getLastname() . ' a été retiré de la structure '.$entity->getOrganization()->getName().'.',
                [$entity->getUser()]
            );
        }
    }
}