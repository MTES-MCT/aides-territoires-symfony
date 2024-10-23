<?php

namespace App\Filter\Backer;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Backer\BackerGroup;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class BackerGroupIdFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        // pour extends AbstractFilter
    }

    public function getDescription(string $resourceClass): array
    {
        $backerGroups = $this->managerRegistry->getRepository(BackerGroup::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        $examples[] = new Example('Choisir un groupe de porteurs d\'aides', null, null);
        foreach ($backerGroups as $backerGroup) {
            $examples[] = new Example($backerGroup->getName(), null, $backerGroup->getId());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_BACKER_GROUP_ID => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_BACKER_GROUP_ID,
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Groupe de porteurs d\'aides.<br><br>Voir aussi <code>/api/backer-groups/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
