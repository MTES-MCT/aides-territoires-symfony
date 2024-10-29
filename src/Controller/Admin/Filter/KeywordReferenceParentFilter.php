<?php

namespace App\Controller\Admin\Filter;

use App\Form\Admin\Reference\KeywordReferenceParentFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class KeywordReferenceParentFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(KeywordReferenceParentFilterType::class);
    }

    public function apply(
        QueryBuilder $queryBuilder,
        FilterDataDto $filterDataDto,
        ?FieldDto $fieldDto,
        EntityDto $entityDto
    ): void {
        if ($filterDataDto->getValue()) {
            $queryBuilder->andWhere(
                sprintf(
                    '%s.parent = entity',
                    $filterDataDto->getEntityAlias(),
                    $filterDataDto->getProperty(),
                    $filterDataDto->getEntityAlias()
                )
            );
        } else {
            $queryBuilder->andWhere(
                sprintf(
                    '%s.parent != entity',
                    $filterDataDto->getEntityAlias(),
                    $filterDataDto->getProperty(),
                    $filterDataDto->getEntityAlias()
                )
            );
        }
    }
}
