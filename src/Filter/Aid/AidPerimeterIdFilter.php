<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidPerimeterIdFilter extends AbstractFilter
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
        return [
            AidSearchFormService::QUERYSTRING_KEY_SEARCH_PERIMETER => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_SEARCH_PERIMETER,
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Le territoire.<br><br>'
                    . 'Voir <code>/api/perimeters/</code> pour la liste complète.<br><br>'
                    . 'Note : passer seulement l\'id du périmètre suffit ('
                    . AidSearchFormService::QUERYSTRING_KEY_SEARCH_PERIMETER . '=70973).</p></div>',
                'openapi' => [
                    'examples' => [
                        new Example('...', null, null),
                        new Example('Auvergnes-Rhônes-Alpes', null, 70973),
                        new Example('Clermont-Ferrand', null, 95861),
                    ]
                ],
            ],
        ];
    }
}
