<?php

namespace App\Form\Type;

use App\Entity\Perimeter\Perimeter;
use App\Repository\Perimeter\PerimeterRepository;
use App\Service\Perimeter\PerimeterService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class PerimeterAutocompleteType extends AbstractType
{
    public function __construct(
        protected PerimeterService $perimeterService
    )
    {
        
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Perimeter::class,
            'placeholder' => 'Tous les territoires',
            'choice_label' => function($entity){
                return $this->perimeterService->getSmartName($entity);
            },
            'data_class' => null,
            'preload' => true,
            'query_builder' => function(PerimeterRepository $perimeterRepository) {
                return $perimeterRepository->getQueryBuilder([
                    'isVisibleToUsers' => true
                ]);
            },
            'filter_query' => function(QueryBuilder $qb, string $query, PerimeterRepository $repository) {
                if (!$query) {
                    return;
                }


                // c'est un code postal
                if (preg_match('/^[0-9]{5}$/', $query)) {
                    $qb
                    ->andWhere('
                        p.zipcodes LIKE :zipcodes
                    ')
                    ->setParameter('zipcodes', '%'.$query.'%');
                    ;
                } else { // c'est une string
                    $qb
                    ->andWhere('
                        MATCH_AGAINST(p.name) AGAINST (:nameMatchAgainst IN BOOLEAN MODE) > 5
                    ')
                    ->setParameter('nameMatchAgainst', $query.'*')
                    ;
                }
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
