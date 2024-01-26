<?php
namespace App\Controller\Admin\Filter\Aid;

use App\Form\Admin\Filter\Aid\AidGenericFilterType;
use App\Repository\Aid\AidRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class AidGenericFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(AidGenericFilterType::class);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if (!$filterDataDto->getValue()) {
            return;
        }
        $state = $filterDataDto->getValue();
        switch ($state) {
            case 'generic':
                $queryBuilder->addCriteria(AidRepository::genericCriteria($filterDataDto->getEntityAlias().'.'));
                break;
            case 'local':
                $queryBuilder->addCriteria(AidRepository::localCriteria($filterDataDto->getEntityAlias().'.'));
                break;
            case 'standard':
                $queryBuilder->addCriteria(AidRepository::decliStandardCriteria($filterDataDto->getEntityAlias().'.'));
                break;
        }

        return;
    }
}