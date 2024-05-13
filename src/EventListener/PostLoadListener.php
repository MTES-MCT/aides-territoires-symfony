<?php

namespace App\EventListener;

use App\Entity\Aid\Aid;
use App\Entity\DataExport\DataExport;
use App\EventListener\Aid\AidListener;
use App\EventListener\DataExport\DataExportListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;

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
        } elseif ($args->getObject() instanceof DataExport) {
            $this->dataExportListener->onPostLoad($args);
        }
    }
}
