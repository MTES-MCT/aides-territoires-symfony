<?php

namespace App\Form\Admin\Filter\Aid;

use App\Entity\Reference\KeywordReference;
use App\Repository\Reference\KeywordReferenceRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class AidKeywordReferenceSearchFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => KeywordReference::class,
            'attr' => [
                'placeholder' => 'Choix mot-clé référent'
            ],
            'choice_label' => function ($keyword) {
                $return = '';
                if ($keyword->getParent() && $keyword->getParent()->getName() != $keyword->getName()) {
                    $return .= $keyword->getParent()->getName().', ';
                }
                $return .= $keyword->getName().' et synonymes';
                return $return;
            },
            'query_builder' => function (EntityRepository $er) {
                $qb = 
                    $er->createQueryBuilder('kr')
                        ->andWhere('kr.intention = 0')
                        ->orderBy('kr.name')
                    ;
                return $qb;
            },
            'data_class' => null,
            'preload' => false,
            'filter_query' => function(QueryBuilder $qb, string $query, KeywordReferenceRepository $repository) {
                if (!$query) {
                    return;
                }

                $qb
                ->andWhere('kr.name = :name')
                ->setParameter('name', $query)
                ;
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}