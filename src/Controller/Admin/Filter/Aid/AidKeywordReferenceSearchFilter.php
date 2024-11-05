<?php

namespace App\Controller\Admin\Filter\Aid;

use App\Entity\Reference\KeywordReference;
use App\Form\Admin\Filter\Aid\AidKeywordReferenceSearchFilterType;
use App\Repository\Reference\KeywordReferenceRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class AidKeywordReferenceSearchFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, mixed $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(AidKeywordReferenceSearchFilterType::class);
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
        /** @var KeywordReferenceRepository $keywordReferenceRepository */
        $keywordReferenceRepository = $queryBuilder->getEntityManager()->getRepository(KeywordReference::class);
        $synonyms = $keywordReferenceRepository->findCustom(['string' => $search, 'noIntention' => true]);

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
            . '.name) AGAINST(:searchKey IN BOOLEAN MODE)
        +
        MATCH_AGAINST('
            . $filterDataDto->getEntityAlias()
            . '.description, '
            . $filterDataDto->getEntityAlias()
            . '.eligibility, '
            . $filterDataDto->getEntityAlias()
            . '.projectExamples) AGAINST(:searchKey IN BOOLEAN MODE)
        ';
        $select .=
            '
        +
        MATCH_AGAINST('
            . $filterDataDto->getEntityAlias()
            . '.name) AGAINST(:objectsStringKey IN BOOLEAN MODE)
        +
        MATCH_AGAINST('
            . $filterDataDto->getEntityAlias()
            . '.description, '
            . $filterDataDto->getEntityAlias()
            . '.eligibility, '
            . $filterDataDto->getEntityAlias()
            . '.projectExamples) AGAINST(:objectsStringKey IN BOOLEAN MODE)
        ';

        $select .= ') as HIDDEN score_key';
        $queryBuilder->addSelect($select)
            ->andHaving('score_key > 1')
            ->orderBy('score_key', 'DESC')
            ->setParameter('searchKey', $search)
            ->setParameter('objectsStringKey', $objectsString)
        ;
    }
}
