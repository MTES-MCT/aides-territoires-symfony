<?php

namespace App\Controller\Admin\Filter\Organization;

use App\Form\Admin\Filter\Organization\HasUserContributorFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class HasUserContributorFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(HasUserContributorFilterType::class);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if ($filterDataDto->getValue() !== null) {
            $queryBuilder
                ->innerJoin($filterDataDto->getEntityAlias() . '.beneficiairies', 'beneficiairies')
                ->andWhere('beneficiairies.isContributor = :isContributor')
            ;
            if ($filterDataDto->getValue() === true) {
                $queryBuilder
                    ->setParameter('isContributor', true);
            } else {
                $queryBuilder
                    ->setParameter('isContributor', false);
            }
        }
    }
}
