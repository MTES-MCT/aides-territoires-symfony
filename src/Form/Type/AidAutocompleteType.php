<?php

namespace App\Form\Type;

use App\Entity\Aid\Aid;
use App\Repository\Aid\AidRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class AidAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Aid::class,
            'placeholder' => 'Tous les territoires',
            'choice_label' => 'name',
            'data_class' => null,
            'preload' => false,
            'query_builder' => function (AidRepository $aidRepository) {
                return $aidRepository->getQueryBuilder([
                    'showInSearch' => true,
                ]);
            },
            'filter_query' => function (QueryBuilder $qb, string $query, AidRepository $repository) {
                if (!$query) {
                    return;
                }

                $qb->andWhere('a.name LIKE :query')
                    ->setParameter('query', '%' . $query . '%');
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
