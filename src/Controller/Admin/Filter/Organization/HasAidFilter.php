<?php
namespace App\Controller\Admin\Filter\Organization;

use App\Form\Admin\Filter\Organization\HasAidFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class HasAidFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(HasAidFilterType::class);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if ($filterDataDto->getValue() !== null) {
            $queryBuilder
                ->innerJoin($filterDataDto->getEntityAlias().'.beneficiairies', 'beneficiairies')
                ->leftJoin('beneficiairies.aids', 'aids')
            ;
            if ($filterDataDto->getValue() === true) {
                $queryBuilder
                    ->andWhere('aids.id IS NOT NULL')
                ;
            } else {
                $queryBuilder
                    ->andWhere('aids.id IS NULL')
                ;
            }
        }
    }
}
