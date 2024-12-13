<?php

namespace App\Filter\Backer;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Backer\BackerGroup;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class BackerGroupFilter extends AbstractFilter
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
        $backerGroups = $this->managerRegistry->getRepository(BackerGroup::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        foreach ($backerGroups as $backerGroup) {
            $examples[] = new Example($backerGroup->getName(), null, $backerGroup->getId());
        }
        return [
            'backerGroup' => [
                'property' => 'backerGroup',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>'
                                    . 'Groupe de porteurs d\'aides.<br><br>'
                                    . 'Voir aussi <code>/api/backer-groups/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
