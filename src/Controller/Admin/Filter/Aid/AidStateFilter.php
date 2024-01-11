<?php
namespace App\Controller\Admin\Filter\Aid;

use App\Form\Admin\Filter\Aid\AidStateFilterType;
use App\Repository\Aid\AidRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class AidStateFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(AidStateFilterType::class);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if (!$filterDataDto->getValue()) {
            return;
        }
        $state = $filterDataDto->getValue();
        switch ($state) {
            case 'live':
                $queryBuilder->addCriteria(AidRepository::showInSearchCriteria($filterDataDto->getEntityAlias().'.'));
                break;
            case 'hidden':
                $queryBuilder->addCriteria(AidRepository::hiddenCriteria($filterDataDto->getEntityAlias().'.'));
                break;
            case 'deadline':
                $queryBuilder
                    ->addCriteria(AidRepository::deadlineCriteria($filterDataDto->getEntityAlias().'.'))
                ;
                break;
            case 'expired':
                $queryBuilder->addCriteria(AidRepository::expiredCriteria($filterDataDto->getEntityAlias().'.'));
                break;
        }

        return;
    }
}