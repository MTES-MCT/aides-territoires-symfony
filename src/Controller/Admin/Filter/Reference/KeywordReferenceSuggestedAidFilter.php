<?php

namespace App\Controller\Admin\Filter\Reference;

use App\Form\Admin\Filter\Reference\KeywordReferenceSuggestedAidFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class KeywordReferenceSuggestedAidFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, mixed $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(KeywordReferenceSuggestedAidFilterType::class);
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
        $queryBuilder->andWhere($filterDataDto->getEntityAlias() . '.aid = :aid');
        $queryBuilder->setParameter('aid', $filterDataDto->getValue());
    }
}
