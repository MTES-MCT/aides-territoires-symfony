<?php

namespace App\EventListener;

use App\Entity\Aid\Aid;
use App\EventListener\Aid\AidListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preUpdate, priority: 500, connection: 'default')]
class PreUpdateListener
{
    public function __construct(
        private AidListener $aidListener
    )
    {
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        // Aides
        if ($args->getObject() instanceof Aid) {
            $this->aidListener->onPreUpdate($args);
        }
    }
}
