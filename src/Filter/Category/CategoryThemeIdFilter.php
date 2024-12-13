<?php

namespace App\Filter\Category;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Category\CategoryTheme;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class CategoryThemeIdFilter extends AbstractFilter
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
        $categoryThemes = $this->managerRegistry->getRepository(CategoryTheme::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        $examples[] = new Example('Choisir une thématique', null, null);
        foreach ($categoryThemes as $categoryTheme) {
            $examples[] = new Example($categoryTheme->getName(), null, $categoryTheme->getId());
        }
        return [
            'category_theme_id' => [
                'property' => 'category_theme_id',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Thématique d\'aides.<br><br>'
                    . 'Voir aussi <code>/api/themes/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
