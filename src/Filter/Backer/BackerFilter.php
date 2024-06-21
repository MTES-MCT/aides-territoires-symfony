<?php
namespace App\Filter\Backer;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Backer\Backer;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class BackerFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        // pour extends AbstractFilter
    }

    public function getDescription(string $resourceClass): array
    {
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
        foreach ($backers as $backer) {
            $examples[] = new Example($backer->getName(), null, $backer->getId());
        }
        return [
            'backerschoice[]' => [
                'property' => 'backerschoice[]',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Porteurs d\'aides.<br><br>Voir aussi <code>/api/backers/</code> pour la liste complète. Vous pouvez passez plusieurs fois le paramètres ..&backerschoice[]=1&backerschoice[]=2</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
