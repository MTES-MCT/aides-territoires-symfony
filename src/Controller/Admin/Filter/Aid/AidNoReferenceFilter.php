<?php

namespace App\Controller\Admin\Filter\Aid;

use App\Form\Admin\Filter\Aid\AidNoReferenceFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class AidNoReferenceFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(AidNoReferenceFilterType::class);
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
            ->leftJoin($filterDataDto->getEntityAlias() . '.projectReferences', 'projectReferences')
            ->andWhere('projectReferences.id IS NULL')
        ;

        return;
    }
}
