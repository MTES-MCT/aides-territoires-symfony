<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\Aid;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidEuropeanSlugFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
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
        $examples = [
            new Example('...', null, null),
            new Example(Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN], null, Aid::SLUG_EUROPEAN),
            new Example(Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_SECTORIAL], null, Aid::SLUG_EUROPEAN_SECTORIAL),
            new Example(
                Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_ORGANIZATIONAL],
                null,
                Aid::SLUG_EUROPEAN_ORGANIZATIONAL
            ),
        ];

        return [
            AidSearchFormService::QUERYSTRING_KEY_EUROPEAN_AID_SLUG => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_EUROPEAN_AID_SLUG,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Aide européennes.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
