<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Category\Category;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidCategoryIdsFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        // pour extends AbstractFilter, on doit implémenter cette méthode
    }

    public function getDescription(string $resourceClass): array
    {
        $categories = $this->managerRegistry->getRepository(Category::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        $examples[] = new Example('Choisir un exemple', null, null);
        foreach ($categories as $category) {
            $examples[] = new Example($category->getName(), null, $category->getId());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_CATEGORY_IDS => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_CATEGORY_IDS,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Les thématiques pour lesquelles vous recherchez des aides. Vous pouvez passer plusieurs fois ce paramètre pour rechercher sur plusieurs thématiques, ex : ...&'.AidSearchFormService::QUERYSTRING_KEY_CATEGORY_IDS.'=1&'.AidSearchFormService::QUERYSTRING_KEY_CATEGORY_IDS.'=5...<br><br>Voir aussi <code>/api/aids/themes/</code> pour la liste complète.</p></div>',
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
