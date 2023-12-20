<?php
namespace App\Filter\Aid;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Organization\OrganizationType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidTargetedAudiencesFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
        $organizationTypes = $this->managerRegistry->getRepository(OrganizationType::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        foreach ($organizationTypes as $organizationType) {
            $examples[] = new Example($organizationType->getName(), null, $organizationType->getSlug());
        }
        return [
            'targeted_audiences' => [
                'property' => 'targeted_audiences',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>La structure pour laquelle vous recherchez des aides.<br><br>Voir aussi <code>/api/aids/audiences/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
?>