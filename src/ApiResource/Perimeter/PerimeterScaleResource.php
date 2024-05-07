<?php

namespace App\ApiResource\Perimeter;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Perimeter\PerimeterController;

#[ApiResource(
    shortName: 'Périmètres',
    operations: [
        new GetCollection(
            name: self::API_OPERATION_NAME,
            uriTemplate: '/perimeters/scales/',
            controller: PerimeterController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: 'Lister tous les choix d\'échelles',
            )
        ),
    ],
)]
class PerimeterScaleResource
{
    const API_OPERATION_NAME = 'perimeter_scales_list';
    const API_GROUP_LIST = 'perimeter_scales:list';
}
