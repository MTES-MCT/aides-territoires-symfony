<?php
namespace App\Filter\Aid;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidTypeGroup;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidMobilizationStepFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
        $aidSteps = $this->managerRegistry->getRepository(AidStep::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        foreach ($aidSteps as $aidStep) {
            $examples[] = new Example($aidStep->getName(), null, $aidStep->getSlug());
        }
        return [
            'mobilization_step' => [
                'property' => 'mobilization_step',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Avancement du projet.<br><br>Voir aussi <code>/api/aids/steps/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
?>