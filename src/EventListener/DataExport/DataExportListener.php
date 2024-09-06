<?php

namespace App\EventListener\DataExport;

use App\Service\DataExport\DataExportService;
use Doctrine\ORM\Event\PostLoadEventArgs;

class DataExportListener
{
    public function __construct(
        protected DataExportService $dataExportService
    ) {
    }

    public function onPostLoad(PostLoadEventArgs $args): void
    {
        $args->getObject()->setUrlExportedFile($this->dataExportService->getUrlExportedFile($args->getObject()));
    }
}
