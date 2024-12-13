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

final class AidCategorySlugsFilter extends AbstractFilter
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
        $categories = $this->managerRegistry->getRepository(Category::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        $examples[] = new Example('Choisir un exemple', null, null);
        foreach ($categories as $category) {
            $examples[] = new Example($category->getName(), null, $category->getSlug());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_CATEGORY_SLUGS => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_CATEGORY_SLUGS,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Les thématiques pour lesquelles '
                    . 'vous recherchez des aides. '
                    . 'Vous pouvez passer plusieurs fois ce paramètre pour rechercher sur plusieurs thématiques, '
                    . 'ex : ...&'
                    . AidSearchFormService::QUERYSTRING_KEY_CATEGORY_SLUGS . '=eau-potable&'
                    . AidSearchFormService::QUERYSTRING_KEY_CATEGORY_SLUGS . '=eau-souterraine...<br><br>'
                    . 'Voir aussi <code>/api/aids/themes/</code> pour la liste complète.</p></div>',
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
