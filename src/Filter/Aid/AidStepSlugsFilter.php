<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidStep;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidStepSlugsFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
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
        $aidSteps = $this->managerRegistry->getRepository(AidStep::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        $examples[] = new Example('Choisir un exemple', null, null);
        foreach ($aidSteps as $aidStep) {
            $examples[] = new Example($aidStep->getName(), null, $aidStep->getSlug());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_AID_STEP_SLUGS => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_AID_STEP_SLUGS,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Avancement du projet.<br><br>'
                    . 'Vous pouvez passer plusieurs fois ce paramètre pour rechercher sur plusieurs types, ex : ...&'
                    . AidSearchFormService::QUERYSTRING_KEY_AID_STEP_SLUGS . '=preop&'
                    . AidSearchFormService::QUERYSTRING_KEY_AID_STEP_SLUGS . '=postop...<br><br>'
                    . 'Voir aussi <code>/api/aids/steps/</code> pour la liste complète.</p></div>',
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