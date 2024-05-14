<?php

namespace App\EventListener\Backer;

use App\Entity\Backer\BackerAskAssociate;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class BackerAskAssociateListener
{
    public function onPostUpdate(PostUpdateEventArgs $args): void {
        /** @var BackerAskAssociate $entity */
        $entity = $args->getObject();
        // les champs qui ont été modifiés
        /** @var EntityManager $manager */
        $manager = $args->getObjectManager();
        $changeSet = $manager->getUnitOfWork()->getEntityChangeSet($entity);

        foreach ($changeSet as $field => $change) {
            // Acceptation d'une demande d'association
            if ($field == 'accepted' && isset($change[1]) && $change[1]) {
                // on ajoute l'organization au porteur
                $entity->getBacker()->addOrganization($entity->getOrganization());
                // sauvegarde
                $manager->persist($entity->getBacker());
                $manager->flush();
            }
        }
    }
}
