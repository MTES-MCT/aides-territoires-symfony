<?php

namespace App\Filter\Aid;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidTypeGroup;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidTypeGroupFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void {}

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
            'aid_type' => [
                'property' => 'aid_type',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Nature de l\'aide.',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
