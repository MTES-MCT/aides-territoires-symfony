<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Organization\OrganizationType;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidOrganizationTypeIdsFilter extends AbstractFilter
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
        $organizationTypes = $this->managerRegistry->getRepository(OrganizationType::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        foreach ($organizationTypes as $organizationType) {
            $examples[] = new Example($organizationType->getName(), null, $organizationType->getId());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_ORGANIZATION_TYPE_IDS => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_ORGANIZATION_TYPE_IDS,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><strong>Nouveau</strong>:'
                    . '<p>Le type de structure pour lequelle vous recherchez des aides. '
                    . 'Vous pouvez passer plusieurs fois ce paramètre pour rechercher sur plusieurs types, ex : ...&'
                    . AidSearchFormService::QUERYSTRING_KEY_ORGANIZATION_TYPE_IDS
                    . '=1&'
                    . AidSearchFormService::QUERYSTRING_KEY_ORGANIZATION_TYPE_IDS
                    . '=2...<br><br>Voir aussi <code>/api/aids/audiences/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'type' => Type::BUILTIN_TYPE_ARRAY,
                    'items' => [
                        'type' => Type::BUILTIN_TYPE_STRING,
                    ],
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
