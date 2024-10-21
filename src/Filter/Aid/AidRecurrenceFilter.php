<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidRecurrence;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidRecurrenceFilter extends AbstractFilter
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
        $aidRecurrences = $this->managerRegistry->getRepository(AidRecurrence::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        foreach ($aidRecurrences as $aidRecurrence) {
            $examples[] = new Example($aidRecurrence->getName(), null, $aidRecurrence->getSlug());
        }
        return [
            'recurrence' => [
                'property' => 'recurrence',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>'
                                    . 'Récurrence.<br><br>Voir aussi <code>/api/aids/recurrences/</code> '
                                    . 'pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
