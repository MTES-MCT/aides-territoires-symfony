<?php

namespace App\ApiResource\Keyword;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\Api\Keyword\KeywordSynonymlistController;

#[ApiResource(
    shortName: 'synonymlists',
    operations: [
        new GetCollection(
            name: self::API_OPERATION_NAME,
            uriTemplate: '/api/synonymlists/classic-list/',
            controller: KeywordSynonymlistController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST]
        ),
    ],
)]
class KeywordSynonymlistClassicResource
{
    public const API_OPERATION_NAME = 'keywordsynonymlist:list_classic';
    public const API_GROUP_LIST = 'keywordsynonymlist:list_classic';
}
