<?php

namespace App\ApiResource\Aid;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Aid\AidController;
use App\Entity\Aid\Aid;

#[ApiResource(
    shortName: 'aid_all',
    operations: [
        new GetCollection(
            name: Aid::API_OPERATION_GET_COLLECTION_ALL,
            uriTemplate: '/aids/all/',
            controller: AidController::class,
            normalizationContext: ['groups' => Aid::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: 'Lister toutes les aides',
                tags: [Aid::API_TAG]
            )
        ),
    ],
    order: ['id' => 'DESC'],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
    paginationClientItemsPerPage: true
)]
class AidAllResource
{
}
