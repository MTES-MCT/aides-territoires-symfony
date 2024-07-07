<?php
namespace App\Filter\Aid;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\Aid;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidEuropeanFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        // method pour extends
    }

    public function getDescription(string $resourceClass): array
    {
        $examples = [
            new Example('...', null, null),
            new Example(Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN], null, Aid::SLUG_EUROPEAN),
            new Example(Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_SECTORIAL], null, Aid::SLUG_EUROPEAN_SECTORIAL),
            new Example(Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_ORGANIZATIONAL], null, Aid::SLUG_EUROPEAN_ORGANIZATIONAL),
        ];

        return [
            'european_aid' => [
                'property' => 'european_aid',
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
