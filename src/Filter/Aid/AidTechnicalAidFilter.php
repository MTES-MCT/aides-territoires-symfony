<?php

namespace App\Filter\Aid;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidTechnicalAidFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void {}

    public function getDescription(string $resourceClass): array
    {
        $aidTypes = $this->managerRegistry->getRepository(AidType::class)->findBy(
            [
                'aidTypeGroup' => $this->managerRegistry->getRepository(AidTypeGroup::class)->findOneBy(['slug' => AidTypeGroup::SLUG_TECHNICAL])
            ],
            ['name' => 'ASC']
        );
        $examples = [];
        foreach ($aidTypes as $aidType) {
            $examples[] = new Example($aidType->getName(), null, $aidType->getSlug());
        }
        return [
            'technical_aids' => [
                'property' => 'technical_aids',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Type d\'aides en ingénierie.<br><br>Voir aussi <code>/api/aids/types/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
