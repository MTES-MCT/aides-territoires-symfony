<?php

namespace App\Controller\Admin\Filter\Backer;

use App\Form\Admin\Filter\Backer\HasNoOrganizationFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class HasNoOrganizationFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(HasNoOrganizationFilterType::class);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if (!$filterDataDto->getValue()) {
            return;
        }
        $queryBuilder
            ->leftJoin($filterDataDto->getEntityAlias() . '.organizations', 'organizations')
            ->andWhere('organizations.id IS NULL')
        ;
    }
}
