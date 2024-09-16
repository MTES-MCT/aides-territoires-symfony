<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidTypeFilter extends AbstractFilter
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
        foreach ($aidTypes as $aidType) {
            $examples[] = new Example($aidType->getName(), null, $aidType->getSlug());
        }
        return [
            'aidTypeSlugs' => [
                'property' => 'aidTypeSlugs',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Type d\'aides financières ou en ingénierie. Vous pouvez passer plusieurs fois ce paramètre pour rechercher sur plusieurs types, ex : ...&aidTypeSlugs=recoverable-advance&aidTypeSlugs=legal-engineering...<br><br><br><br>Voir aussi <code>/api/aids/types/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
