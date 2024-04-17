<?php
namespace App\Controller\Admin\Filter\Aid;

use App\Form\Admin\Filter\Aid\AidReferenceSearchFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class AidReferenceSearchFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(AidReferenceSearchFilterType::class);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if (!$filterDataDto->getValue()) {
            return;
        }
        $search = $filterDataDto->getValue()->getName();

        $queryBuilder->addSelect(
            '
            (
            MATCH_AGAINST('.$filterDataDto->getEntityAlias().'.name) AGAINST(:search IN BOOLEAN MODE)
            + 
            MATCH_AGAINST('.$filterDataDto->getEntityAlias().'.description, '.$filterDataDto->getEntityAlias().'.eligibility, '.$filterDataDto->getEntityAlias().'.projectExamples) AGAINST(:search IN BOOLEAN MODE)
            ) as HIDDEN score
            '
        )
        ->andHaving('score > 1')
        ->orderBy('score', 'DESC')
        ->setParameter('search', $search)
        ;

        return;
    }
}