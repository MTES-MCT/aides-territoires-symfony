<?php

namespace App\ApiResource\Perimeter;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Perimeter\PerimeterController;
use App\Entity\Perimeter\Perimeter;

#[ApiResource(
    shortName: 'perimeter_scales',
    operations: [
        new GetCollection(
            name: self::API_OPERATION_NAME,
            uriTemplate: '/perimeters/scales/',
            controller: PerimeterController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: 'Lister tous les choix d\'échelles',
                tags: [Perimeter::API_TAG]
            )
        ),
    ],
)]
class PerimeterScaleResource
{
    public const API_OPERATION_NAME = 'perimeter_scales_list';
    public const API_GROUP_LIST = 'perimeter_scales:list';
}
