<?php

namespace App\Controller\Admin\Filter;

use App\Form\Admin\Filter\UserCountyFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class UserCountyFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, mixed $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(UserCountyFilterType::class);
    }

    public function apply(
        QueryBuilder $queryBuilder,
        FilterDataDto $filterDataDto,
        ?FieldDto $fieldDto,
        EntityDto $entityDto
    ): void {
        if (!$filterDataDto->getValue()) {
            return;
        }

        $queryBuilder
            ->innerJoin('entity.organizations', 'organizationsForDept')
            ->innerJoin('organizationsForDept.perimeterDepartment', 'perimeterDepartment')
            ->andWhere('perimeterDepartment.id = :idPerimeterDepartment')
            ->setParameter('idPerimeterDepartment', $filterDataDto->getValue());
        ;

        return;
    }
}
