<?php

namespace App\Controller\Admin\Filter\Backer;

use App\Entity\Perimeter\Perimeter;
use App\Form\Admin\Filter\Backer\BackerPerimeterFilterType;
use App\Repository\Perimeter\PerimeterRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class BackerPerimeterFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, mixed $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(BackerPerimeterFilterType::class);
    }

    public function apply(
        QueryBuilder $queryBuilder,
        FilterDataDto $filterDataDto,
        ?FieldDto $fieldDto,
        EntityDto $entityDto,
    ): void {
        if (!$filterDataDto->getValue()) {
            return;
        }

        /** @var PerimeterRepository $perimeterRepository */
        $perimeterRepository = $queryBuilder->getEntityManager()->getRepository(Perimeter::class);

        $ids = $perimeterRepository->getIdPerimetersContainedIn(['perimeter' => $filterDataDto->getValue()]);
        $comparison = $filterDataDto->getComparison();

        $queryBuilder
            ->innerJoin(sprintf('%s.perimeter', $filterDataDto->getEntityAlias()), 'perimeterFilter')
        ;

        if ('eq' === $comparison) {
            $queryBuilder->andWhere('perimeterFilter.id IN (:ids)')
                ->setParameter('ids', $ids);
        } else if ('eq_strict' === $comparison) {
            $queryBuilder->andWhere('perimeterFilter.id = :id')
                ->setParameter('id', $filterDataDto->getValue());
        } else {
            $queryBuilder->andWhere('perimeterFilter.id NOT IN (:ids)')
                ->setParameter('ids', $ids);
        }
    }
}