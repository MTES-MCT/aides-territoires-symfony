<?php 

namespace App\ApiResource\Keyword;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Aid\AidController;
use App\Controller\Api\Keyword\KeywordSynonymlistController;
use App\Controller\Api\Perimeter\PerimeterController;
use App\Entity\Aid\Aid;

#[ApiResource(
    shortName: 'synonymlists',
    operations: [
        new GetCollection(
            name: self::API_OPERATION_NAME,
            uriTemplate: '/api/synonymlists/classic-list/',
            controller: KeywordSynonymlistController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            // openapi: new Model\Operation(
            //     summary: 'Lister tous les choix d\'échelles', 
            // )
        ),
    ],
)]
class KeywordSynonymlistClassicResource
{
    const API_OPERATION_NAME = 'keywordsynonymlist:list_classic';
    const API_GROUP_LIST = 'keywordsynonymlist:list_classic';
}