<?php

namespace App\Filter\Backer;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Backer\Backer;
use App\Repository\Backer\BackerRepository;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class BackerIdsFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        // pour extends AbstractFilter
    }

    public function getDescription(string $resourceClass): array
    {
        /** @var BackerRepository $backerRepository */
        $backerRepository = $this->managerRegistry->getRepository(Backer::class);
        $backers = $backerRepository->findCustom([
            'hasFinancedAids' => true,
            'active' => true,
            'orderBy' => [
                'sort' => 'b.name',
                'order' => 'ASC'
            ]
        ]);
        $examples = [];
        $examples[] = new Example('Choisir un exemple', null, null);
        foreach ($backers as $backer) {
            $examples[] = new Example($backer->getName(), null, $backer->getId());
        }
        return [
            AidSearchFormService::QUERYSTRING_KEY_BACKER_IDS => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_BACKER_IDS,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Porteurs d\'aides.<br><br>Voir aussi <code>/api/backers/</code> pour la liste complète. Vous pouvez passez plusieurs fois le paramètres ..&'.AidSearchFormService::QUERYSTRING_KEY_BACKER_IDS.'=1&'.AidSearchFormService::QUERYSTRING_KEY_BACKER_IDS.'=2</p></div>',
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
