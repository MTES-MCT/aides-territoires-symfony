<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidProjectReferenceFilter extends AbstractFilter
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
            'project_reference_id' => [
                'property' => 'project_reference_id',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Le projet référent.<br><br>'
                    . 'Voir <code>/api/project-references/</code> pour la liste complète.<br><br>'
                    . 'Note : passer seulement l\'id du projet référent (project_reference_id=2).</p></div>',
                'openapi' => [
                    'examples' => [
                        new Example('...', null, null),
                        new Example('Développer les infrastructures de covoiturage', null, 1),
                        new Example('Mise en place d’un réseau de chaleur', null, 11),
                    ]
                ],
            ],
        ];
    }
}
