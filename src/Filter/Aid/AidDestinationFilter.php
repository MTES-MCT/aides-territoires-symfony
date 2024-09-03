<?php

namespace App\Filter\Aid;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidStep;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidDestinationFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void {}

    public function getDescription(string $resourceClass): array
    {
        $aidDestinations = $this->managerRegistry->getRepository(AidDestination::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        foreach ($aidDestinations as $aidDestination) {
            $examples[] = new Example($aidDestination->getName(), null, $aidDestination->getSlug());
        }
        return [
            'destinations' => [
                'property' => 'destinations',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Actions concernées.<br><br>Voir aussi <code>/api/aids/destinations/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
