<?php
namespace App\Filter\Perimeter;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class PerimeterTextFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        // ajouté pour être conforme à l'extends
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'q' => [
                'property' => 'q',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Rechercher par nom.<br><br>Note : il est possible d\'avoir des résultats pertinents avec seulement le début du nom,     ou un nom légerement erroné.</p></div>',
                'openapi' => [
                    'examples' => [
                        new Example('lyon', null, 'lyon'),
                        new Example('par', null, 'par'),
                        new Example('grenble', null, 'grenble')
                    ],
                ],
            ],
        ];
    }
}
