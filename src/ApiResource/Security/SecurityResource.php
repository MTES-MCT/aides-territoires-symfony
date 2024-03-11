<?php 

namespace App\ApiResource\Security;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Perimeter\PerimeterController;

#[ApiResource(
    shortName: 'Connexion',
    operations: [
        new Post(
            name: self::API_OPERATION_NAME,
            uriTemplate: '/connexion/',
            openapi: new Model\Operation(
                summary: 'Pour récupérer le Bearer Token', 
                description: 'Appellez cette url avec <strong>X-AUTH-TOKEN=VotreToken</strong> dans les headers pour obtenir le Bearer Token. Ne fonctionne <strong>PAS</strong> actuellement dans le swagger. Utilisez un script ou un client REST pour tester.',
            )
        ),
    ],
)]
class SecurityResource
{
    const API_OPERATION_NAME = 'security_post';
    // const API_GROUP_LIST = 'perimeter_scales:list';
}