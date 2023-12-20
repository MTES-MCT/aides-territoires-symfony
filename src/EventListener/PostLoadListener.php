<?php

namespace App\EventListener;

use App\Entity\Aid\Aid;
use App\Entity\DataExport\DataExport;
use App\Entity\Log\LogAdminAction;
use App\EventListener\Aid\AidListener;
use App\EventListener\DataExport\DataExportListener;
use App\Service\User\UserService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\FirewallMapInterface;

#[AsDoctrineListener(event: Events::postLoad, priority: 500, connection: 'default')]
class PostLoadListener
{
    public function __construct(
        protected AidListener $aidListener,
        protected DataExportListener $dataExportListener
    ) {}

    public function postLoad(PostLoadEventArgs $args): void
    {
        if ($args->getObject() instanceof Aid) {
            $this->aidListener->onPostLoad($args);
        } else if ($args->getObject() instanceof DataExport) {
            $this->dataExportListener->onPostLoad($args);
        }
    }
}