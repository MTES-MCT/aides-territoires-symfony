<?php

namespace App\Controller\Admin\Filter\Aid;

use App\Entity\Perimeter\Perimeter;
use App\Form\Admin\Filter\Aid\AidPerimeterFilterType;
use App\Repository\Perimeter\PerimeterRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class AidPerimeterFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, mixed $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(AidPerimeterFilterType::class);
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

        /** @var PerimeterRepository $perimeterRepository */
        $perimeterRepository = $queryBuilder->getEntityManager()->getRepository(Perimeter::class);

        $ids = $perimeterRepository->getIdPerimetersContainedIn(['perimeter' => $filterDataDto->getValue()]);

        $queryBuilder
            ->innerJoin(sprintf('%s.perimeter', $filterDataDto->getEntityAlias()), 'perimeterFilter')
            ->andWhere('perimeterFilter.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;
    }
}
