<?php

namespace App\Filter\Perimeter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class PerimeterZipcodesFilter extends AbstractFilter
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
        $examples = [];
        $examples[] = new Example(91640, null, 91640);
        return [
            'zipcodes' => [
                'property' => 'zipcodes',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>'
                                    . 'Filtrer par code postaux. '
                                    . 'Vous pouvez passer plusieurs fois le paramètre pour en rechercher plusieurs, '
                                    . 'ex: &zipcodes=91400&zipcodes=91640</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
