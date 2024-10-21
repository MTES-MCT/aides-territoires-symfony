<?php

namespace App\Controller\Admin\Filter\Aid;

use App\Entity\Reference\KeywordReference;
use App\Form\Admin\Filter\Aid\AidReferenceSearchFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class AidReferenceSearchFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(AidReferenceSearchFilterType::class);
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
        $search = $filterDataDto->getValue()->getName();
        $synonyms = $queryBuilder->getEntityManager()->getRepository(KeywordReference::class)
            ->findCustom(['string' => $search, 'noIntention' => true]);

        $objectsString = '';
        foreach ($synonyms as $synonym) {
            $objectsString .= trim($synonym) . ',';
        }
        $objectsString = substr($objectsString, 0, -1);

        $select = '(';
        $select .=
            '
        MATCH_AGAINST('
            . $filterDataDto->getEntityAlias()
            . '.name) AGAINST(:search IN BOOLEAN MODE)
        +
        MATCH_AGAINST('
            . $filterDataDto->getEntityAlias()
            . '.description, '
            . $filterDataDto->getEntityAlias()
            . '.eligibility, '
            . $filterDataDto->getEntityAlias()
            . '.projectExamples) AGAINST(:search IN BOOLEAN MODE)
        ';
        $select .=
            '
        +
        MATCH_AGAINST('
            . $filterDataDto->getEntityAlias()
            . '.name) AGAINST(:objectsString IN BOOLEAN MODE)
        +
        MATCH_AGAINST('
            . $filterDataDto->getEntityAlias()
            . '.description, '
            . $filterDataDto->getEntityAlias()
            . '.eligibility, '
            . $filterDataDto->getEntityAlias()
            . '.projectExamples) AGAINST(:objectsString IN BOOLEAN MODE)
        ';

        $select .= ') as HIDDEN score';
        $queryBuilder->addSelect($select)
            ->andHaving('score > 1')
            ->orderBy('score', 'DESC')
            ->setParameter('search', $search)
            ->setParameter('objectsString', $objectsString)
        ;

        return;
    }
}
