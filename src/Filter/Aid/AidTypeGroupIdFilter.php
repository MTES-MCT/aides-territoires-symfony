<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidTypeGroup;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidTypeGroupIdFilter extends AbstractFilter
{
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
        $examples[] = new Example('Choisir un exemple', null, null);
        foreach ($aidTypeGroups as $aidTypeGroup) {
            $examples[] = new Example($aidTypeGroup->getName(), null, $aidTypeGroup->getId());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_AID_TYPE_GROUP_ID => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_AID_TYPE_GROUP_ID,
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => 'Id du groupe de la nature de l\'aide.',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
