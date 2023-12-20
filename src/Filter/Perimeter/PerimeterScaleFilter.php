<?php
namespace App\Filter\Perimeter;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Perimeter\Perimeter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class PerimeterScaleFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
        $examples = [];
        foreach (Perimeter::SCALES_TUPLE as $scale) {
            $examples[] = new Example($scale['name'], null, $scale['slug']);
        }
        return [
            'scale' => [
                'property' => 'scale',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Filtrer par l\'échelle.<br><br>Voir <code>/api/perimeters/scales/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
?>