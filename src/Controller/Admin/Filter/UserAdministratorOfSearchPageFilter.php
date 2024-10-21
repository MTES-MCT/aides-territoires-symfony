<?php

namespace App\Controller\Admin\Filter;

use App\Form\Admin\Filter\UserAdministratorOfSearchPageFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class UserAdministratorOfSearchPageFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(UserAdministratorOfSearchPageFilterType::class);
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

        if ($filterDataDto->getValue() === 1) {
            $queryBuilder
                ->innerJoin(
                    sprintf('%s.searchPages', $filterDataDto->getEntityAlias()),
                    'searchPages'
                );
            return;
        } elseif ($filterDataDto->getValue() === 0) {
            $queryBuilder
                ->leftJoin(sprintf('%s.searchPages', 'searchPages'), $filterDataDto->getEntityAlias())
                ->andWhere('searchPages.id IS NULL')
            ;
            return;
        }


        return;
    }
}
