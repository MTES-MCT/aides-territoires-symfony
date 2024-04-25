<?php

namespace App\EventListener\Organization;

use App\Entity\Aid\Aid;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationAccess;
use App\Entity\Organization\OrganizationInvitation;
use App\Entity\Project\Project;
use App\Entity\User\User;
use App\Service\Organization\OrganizationService;
use Doctrine\ORM\Event\PostRemoveEventArgs;
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

        // recupere l'organization
        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        $changeOrganization = $changeSet['organization'] ?? null;
        $organization = null;
        if (isset($changeOrganization[0]) && $changeOrganization[0] instanceof Organization) {
            $organization = $changeOrganization[0];
        }

        // supprimes les invitation également
        $email = $entity->getUser() ? $entity->getUser()->getEmail() : null;
        if ($email && $organization) {
            $organizationInvitations = $this->managerRegistry->getRepository(OrganizationInvitation::class)->findBy([
                'email' => $email,
                'organization' => $organization
            ]);

            foreach ($organizationInvitations as $organizationInvitation) {
                $this->managerRegistry->getManager()->remove($organizationInvitation);
            }   
        }
    }

    public function onPostRemove(PostRemoveEventArgs $args)
    {
        /** @var OrganizationAccess $entity */
        $entity = $args->getObject();

        // recupere l'organization
        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        $changeOrganization = $changeSet['organization'] ?? null;
        $organization = null;
        if (isset($changeOrganization[0]) && $changeOrganization[0] instanceof Organization) {
            $organization = $changeOrganization[0];
        }

        // si le membre à fait des aides ou des projets pour l'organization, on les transfert au premier admin
        if ($organization) {
            // recupere le premier admin
            $admin = null;
            foreach ($organization->getOrganizationAccesses() as $organizationAccess) {
                if ($organizationAccess->isAdministrator() && $organizationAccess->getUser() !== $entity->getUser()) {
                    $admin = $organizationAccess->getUser();
                    break;
                }
            }

            // si on a un admin
            if ($admin instanceof User) {
                $needFlush = false;
                $aids = $this->managerRegistry->getRepository(Aid::class)->findCustom([
                    'organization' => $organization,
                    'author' => $entity->getUser()
                ]);
                
                foreach ($aids as $aid) {
                    $aid->setAuthor($admin);
                    $this->managerRegistry->getManager()->persist($aid);
                    $needFlush = true;
                }

                $projects = $this->managerRegistry->getRepository(Project::class)->findCustom([
                    'organization' => $organization,
                    'author' => $entity->getUser()
                ]);

                foreach ($projects as $project) {
                    $project->setAuthor($admin);
                    $this->managerRegistry->getManager()->persist($project);
                    $needFlush = true;
                }
            }

            if ($needFlush) {
                // sauvegarde modifs
                $this->managerRegistry->getManager()->flush();
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