<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidType;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidTypeIdsFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
        $aidTypes = $this->managerRegistry->getRepository(AidType::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        $examples[] = new Example('Choisir un exemple', null, null);
        foreach ($aidTypes as $aidType) {
            $examples[] = new Example($aidType->getName(), null, $aidType->getId());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_AID_TYPE_IDS => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_AID_TYPE_IDS,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Type d\'aides financières ou en ingénierie par ids. Vous pouvez passer plusieurs fois ce paramètre pour rechercher sur plusieurs types, ex : ...&'.AidSearchFormService::QUERYSTRING_KEY_AID_TYPE_IDS.'=3&'.AidSearchFormService::QUERYSTRING_KEY_AID_TYPE_IDS.'=8...<br><br><br><br>Voir aussi <code>/api/aids/types/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'type' => Type::BUILTIN_TYPE_ARRAY,
                    'items' => [
                        'type' => Type::BUILTIN_TYPE_INT,
                    ],
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
