<?php
namespace App\Filter\Reference;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class ProjectReferenceTextFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'q' => [
                'property' => 'q',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Rechercher par nom.<br><br>Note : il est possible d\'avoir des résultats pertinents avec seulement une partie du nom.</p></div>',
                'openapi' => [
                    'examples' => [
                        new Example('Développer les infrastructures de covoiturage', null, 'Développer les infrastructures de covoiturage'),
                        new Example('covoiturage', null, 'coivoiturage'),
                    ],
                ],
            ],
        ];
    }
}
