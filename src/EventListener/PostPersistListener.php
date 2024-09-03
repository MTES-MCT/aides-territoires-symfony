<?php

namespace App\EventListener;

use App\Entity\Log\LogAdminAction;
use App\Entity\User\User;
use App\EventListener\User\UserListener;
use App\Service\User\UserService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\FirewallMapInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
class PostPersistListener
{
    public function __construct(
        protected RequestStack $requestStack,
        protected UserService $userService,
        protected FirewallMapInterface $firewallMapInterface,
        protected ManagerRegistry $managerRegistry,
        protected UserListener $userListener
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        // apres inscription utilisateur
        if ($args->getObject() instanceof User) {
            $this->userListener->onPostPersist($args);
        }

        // LOG ADMIN
        if ($this->requestStack && $this->requestStack->getCurrentRequest()) {
            $firewallConfig = $this->firewallMapInterface->getFirewallConfig($this->requestStack->getCurrentRequest());
            if ($firewallConfig->getName() == LogAdminAction::FIREWALL_ADMIN_NAME && !$args->getObject() instanceof LogAdminAction) {
                $logAdminAction = new LogAdminAction();
                // vérification du format id
                $objectId = null;
                if (method_exists($args->getObject(), 'getId') && $args->getObject()->getId() && is_int($args->getObject()->getId())) {
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
                $logAdminAction->setActionFlag(LogAdminAction::ACTION_FLAG_INSERT);
                $logAdminAction->setAdmin($this->userService->getUserLogged());

                $changeMessage = [
                    'added' => []
                ];
                $logAdminAction->setChangeMessage($changeMessage);

                // sauvegarde
                $args->getObjectManager()->persist($logAdminAction);
                $args->getObjectManager()->flush();
            }
        }
    }
}
