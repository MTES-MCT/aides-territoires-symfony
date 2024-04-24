<?php

namespace App\EventListener\Backer;

use App\Entity\Backer\Backer;
use App\Entity\Organization\Organization;
use App\Service\Notification\NotificationService;
use App\Service\Organization\OrganizationService;
use App\Service\Various\ParamService;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class BackerListener
{
    public function __construct(
        protected OrganizationService $organizationService,
        protected ParamService $paramService,
        protected RouterInterface $routerInterface
    ) {
        
    }


    public function onPostUpdate(PostUpdateEventArgs $args): void {
        /** @var Backer $backer */
        $backer = $args->getObject();
        // les champs qui ont été modifiés
        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($backer);
        foreach ($changeSet as $field => $change) {
            // Publication d'une aide
            if ($field == 'active' && isset($change[1]) && $change[1] == true) {
                $backerUrl = $this->routerInterface->generate('app_backer_details', ['id' => $backer->getId(), 'slug' => $backer->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
                // recupere la structure du porteur d'aides
                foreach ($backer->getOrganizations() as $organization) {
                    $this->organizationService->sendNotificationToMembers(
                        $organization,
                        'Validation de '.$backer->getName(),
                        'La fiche du porteur d\'aides '.$backer->getName().' a été validée. Vous pouvez la consulter en cliquant sur <a href="'.$backerUrl.'" title="Voir la fiche du porteur d\'aides">ce lien</a>.',
                    );
                }
            }
        }
    }
}