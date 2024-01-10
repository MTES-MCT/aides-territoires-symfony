<?php
namespace App\Controller\Admin\Filter;

use App\Entity\Perimeter\Perimeter;
use App\Form\Admin\Filter\UserCountyFilterType;
use App\Form\Admin\Filter\UserRoleFilterType;
use App\Repository\Perimeter\PerimeterRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class UserCountyFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(UserCountyFilterType::class);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if (!$filterDataDto->getValue()) {
            return;
        }
        $ids = $queryBuilder->getEntityManager()->getRepository(Perimeter::class)->getIdPerimetersContainedIn(['perimeter' => $filterDataDto->getValue()]);

        $queryBuilder
            ->innerJoin(sprintf('%s.%s', $filterDataDto->getEntityAlias(), $filterDataDto->getProperty()), 'perimeter')
            ->andWhere('perimeter.id IN (:ids)')
            ->setParameter('ids', $ids);
            ;

        return;
    }
}
