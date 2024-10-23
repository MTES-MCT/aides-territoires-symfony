<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Aid\AidRecurrence;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidRecurrenceIdFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
        $aidRecurrences = $this->managerRegistry->getRepository(AidRecurrence::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        $examples[] = new Example('Choisir un exemple', null, null);
        foreach ($aidRecurrences as $aidRecurrence) {
            $examples[] = new Example($aidRecurrence->getName(), null, $aidRecurrence->getId());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_AID_RECURRENCE_ID => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_AID_RECURRENCE_ID,
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Récurrence par id.<br><br>Voir aussi <code>/api/aids/recurrences/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
