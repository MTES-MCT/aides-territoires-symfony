<?php

namespace App\Controller\Admin\Filter\User;

use App\Form\Admin\Filter\User\UserOganizationTypeFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class UserOganizationTypeFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(UserOganizationTypeFilterType::class);
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
            ->innerJoin('entity.organizations', 'organizationsForOt')
            ->innerJoin('organizationsForOt.organizationType', 'organizationTypeForFilter')
            ->andWhere('organizationTypeForFilter.id = :idOrganizationTypeForFilter')
            ->setParameter('idOrganizationTypeForFilter', $filterDataDto->getValue());
        ;


        return;
    }
}
