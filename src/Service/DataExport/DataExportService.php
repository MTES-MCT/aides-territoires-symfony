<?php

namespace App\Service\DataExport;

use App\Entity\DataExport\DataExport;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DataExportService
{
    public function __construct(
        protected ParameterBagInterface $parameterBagInterface
    )
    {
    }

    public function getUrlExportedFile(DataExport $dataExport) : ?string {
        try {
            return $this->parameterBagInterface->get('cloud_image_url').$dataExport->getExportedFile();
        } catch (\Exception $e) {
            return null;
        }
    }
}