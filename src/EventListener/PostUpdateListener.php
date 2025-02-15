<?php

namespace App\EventListener;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerAskAssociate;
use App\Entity\Log\LogAdminAction;
use App\EventListener\Aid\AidListener;
use App\EventListener\Backer\BackerAskAssociateListener;
use App\EventListener\Backer\BackerListener;
use App\Service\User\UserService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsDoctrineListener(event: Events::postUpdate, priority: 500, connection: 'default')]
class PostUpdateListener
{
    public function __construct(
        private RequestStack $requestStack,
        private UserService $userService,
        private AidListener $aidListener,
        private BackerListener $backerListener,
        private BackerAskAssociateListener $backerAskAssociateListener,
        private Security $security
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        // Aides
        if ($args->getObject() instanceof Aid) {
            $this->aidListener->onPostUpdate($args);
        }

        // Porteurs d'aides
        if ($args->getObject() instanceof Backer) {
            $this->backerListener->onPostUpdate($args);
        }

        // demande association porteur
        if ($args->getObject() instanceof BackerAskAssociate) {
            $this->backerAskAssociateListener->onPostUpdate($args);
        }

        // LOG ADMIN
        $this->logAdmin($args);
    }

    private function logAdmin(PostUpdateEventArgs $args): void
    {
        if (
            $this->requestStack->getCurrentRequest()
            && $this->security->getFirewallConfig($this->requestStack->getCurrentRequest())->getName()
                == LogAdminAction::FIREWALL_ADMIN_NAME
            && !$args->getObject() instanceof LogAdminAction
        ) {
            // l'action d'amin a loguer
            $logAdminAction = $this->getLogAdminAction($args);

            $changeMessage = [
                'changed' => [
                    'fields' => []
                ]
            ];
            /** @var EntityManager $manager */
            $manager = $args->getObjectManager();
            $changeSet = $manager->getUnitOfWork()->getEntityChangeSet($args->getObject());
            foreach ($changeSet as $field => $change) {
                if (!in_array($field, LogAdminAction::NOT_ADMIN_LOGGED_FIELDS)) {
                    $changeMessage['changed']['fields'][] = $field;
                }
            }
            $logAdminAction->setChangeMessage($changeMessage);


            // sauvegarde
            $args->getObjectManager()->persist($logAdminAction);
            $args->getObjectManager()->flush();
        }
    }

    private function getLogAdminAction(PostUpdateEventArgs $args): LogAdminAction
    {
        $logAdminAction = new LogAdminAction();
        // vérification du format id
        $objectId = null;
        if (
            method_exists($args->getObject(), 'getId')
            && $args->getObject()->getId()
            && is_int($args->getObject()->getId())
        ) {
            $objectId = $args->getObject()->getId();
        }
        $logAdminAction->setObjectClass(get_class($args->getObject()));
        $logAdminAction->setObjectId($objectId);
        if (method_exists($args->getObject(), '__toString')) {
            $objectRepr = $args->getObject()->__toString();
        } else {
            $objectRepr = get_class($args->getObject()) . ' : ' . $args->getObject()->getId();
        }
        $logAdminAction->setObjectRepr($objectRepr);
        $logAdminAction->setActionFlag(LogAdminAction::ACTION_FLAG_UPDATE);
        $logAdminAction->setAdmin($this->userService->getUserLogged());

        return $logAdminAction;
    }
}
