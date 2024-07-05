<?php

namespace App\EventListener\Backer;

use App\Entity\Backer\BackerAskAssociate;
use App\Service\Notification\NotificationService;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class BackerAskAssociateListener
{
    public function __construct(
        private NotificationService $notificationService,
        private RouterInterface $routerInterface
    )
    {
    }

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

                // url fiche backer
                $backerUrl = $this->routerInterface->generate('app_backer_details', ['id' => $entity->getBacker()->getId(), 'slug' => $entity->getBacker()->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);

                // envoi de la notification à tous les utilisateurs de la structure
                if ($entity->getOrganization()) {
                    foreach ($entity->getBacker()->getOrganizations() as $organization) {
                        foreach ($organization->getBeneficiairies() as $beneficiairy) {
                            $this->notificationService->addNotification(
                                $beneficiairy,
                                'Association de la structure '.$entity->getOrganization()->getName(). ' avec le porteur d\'aides '.$entity->getBacker()->getName(),
                                'La fiche du porteur d\'aides '.$entity->getBacker()->getName().' a été associée avec la structure '.$entity->getOrganization()->getName().'. Vous pouvez la consulter en cliquant sur <a href="'.$backerUrl.'" title="Voir la fiche du porteur d\'aides">ce lien</a>.',
                            );
                        }
                    }
                }

            } elseif ($field == 'refused' && isset($change[1]) && $change[1]) {
                // envoi de la notification à tous les utilisateurs de la structure
                $messageRefus = $entity->getRefusedDescription() ? $entity->getRefusedDescription() : 'Votre demande à été refusée.';
                if ($entity->getOrganization()) {
                    foreach ($entity->getBacker()->getOrganizations() as $organization) {
                        foreach ($organization->getBeneficiairies() as $beneficiairy) {
                            $this->notificationService->addNotification(
                                $beneficiairy,
                                'Refus de l\'association de la structure '.$entity->getOrganization()->getName(). ' avec le porteur d\'aides '.$entity->getBacker()->getName(),
                                $messageRefus
                            );
                        }
                    }
                }
            }
        }
    }
}
