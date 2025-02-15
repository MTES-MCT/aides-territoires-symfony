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

final class AidTypeGroupSlugFilter extends AbstractFilter
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
        $aidTypeGroups = $this->managerRegistry->getRepository(AidTypeGroup::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        $examples[] = new Example('Choisir un exemple', null, null);
        foreach ($aidTypeGroups as $aidTypeGroup) {
            $examples[] = new Example($aidTypeGroup->getName(), null, $aidTypeGroup->getSlug());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_AID_TYPE_GROUP_SLUG,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Groupe de la nature de l\'aide.',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
