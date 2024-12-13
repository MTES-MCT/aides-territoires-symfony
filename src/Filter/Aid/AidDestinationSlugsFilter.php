<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidDestination;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidDestinationSlugsFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        // ajouté pour être conforme à l'extends
    }

    public function getDescription(string $resourceClass): array
    {
        $aidDestinations = $this->managerRegistry->getRepository(AidDestination::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        $examples[] = new Example('Choisir un exemple', null, null);
        foreach ($aidDestinations as $aidDestination) {
            $examples[] = new Example($aidDestination->getName(), null, $aidDestination->getSlug());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_AID_DESTINATION_SLUGS => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_AID_DESTINATION_SLUGS,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Actions concernées.<br><br>'
                    . 'Vous pouvez passer plusieurs fois ce paramètre pour rechercher sur plusieurs thématiques, '
                    . 'ex : ...&'
                    . AidSearchFormService::QUERYSTRING_KEY_AID_DESTINATION_SLUGS
                    . '=supply&'
                    . AidSearchFormService::QUERYSTRING_KEY_AID_DESTINATION_SLUGS
                    . '=investment...<br><br>Voir aussi <code>/api/aids/destinations/</code> '
                    . 'pour la liste complète.</p></div>',
                'openapi' => [
                    'type' => Type::BUILTIN_TYPE_ARRAY,
                    'items' => [
                        'type' => Type::BUILTIN_TYPE_STRING,
                    ],
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
