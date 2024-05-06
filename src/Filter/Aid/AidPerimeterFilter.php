<?php
namespace App\Filter\Aid;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidTypeGroup;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidPerimeterFilter extends AbstractFilter
{
    // empty method
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
        $aidTypeGroups = $this->managerRegistry->getRepository(AidTypeGroup::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        foreach ($aidTypeGroups as $aidTypeGroup) {
            $examples[] = new Example($aidTypeGroup->getName(), null, $aidTypeGroup->getSlug());
        }
        return [
            'perimeter' => [
                'property' => 'perimeter',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Le territoire.<br><br>Voir <code>/api/perimeters/</code> pour la liste complète.<br><br>Note : passer seulement l\'id du périmètre suffit (perimeter=70973).</p></div>',
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
