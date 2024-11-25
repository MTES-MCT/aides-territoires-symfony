<?php

namespace App\ApiResource\Keyword;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Keyword\KeywordSynonymlistController;
use App\Entity\Keyword\KeywordSynonymlist;

#[ApiResource(
    shortName: 'synonymlists',
    operations: [
        new GetCollection(
            name: self::API_OPERATION_NAME,
            uriTemplate: '/synonymlists/classic-list/',
            controller: KeywordSynonymlistController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: 'Ancienne liste de mots clés',
                tags: [KeywordSynonymlist::API_TAG],
            ),
        ),
    ],
)]
class KeywordSynonymlistClassicResource
{
    public const API_OPERATION_NAME = 'keywordsynonymlist:list_classic';
    public const API_GROUP_LIST = 'keywordsynonymlist:list_classic';
}
