<?php

namespace App\Form\Type;

use App\Entity\Perimeter\Perimeter;
use App\Repository\Perimeter\PerimeterRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class PerimeterCityAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Perimeter::class,
            'placeholder' => 'Toutes les communes',
            'choice_label' => function($entity){

                if($entity->getScale() == 1){
                    $return = $entity->getName();
                    if (is_array($entity->getZipcodes())) {
                        $return .= ' (COMMUNE - '.implode(',', $entity->getZipcodes()).')';
                    } else {
                        $return .=  ' (COMMUNE - '.(string) $entity->getZipcodes().')';
                    }
                    return $return;
                }else{
                    if(isset(Perimeter::SCALES_FOR_SEARCH[$entity->getScale()]['name'])){
                        return $entity->getName(). ' ('.Perimeter::SCALES_FOR_SEARCH[$entity->getScale()]['name'].')';
                    }else{
                        return $entity->getName();
                    }
                    
                }
                
            },
            'data_class' => null,
            'query_builder' => function (PerimeterRepository $perimeterRepository) {
                return $perimeterRepository->getQueryBuilder([
                    'isVisibleToUsers' => true,
                    'scale' => Perimeter::SCALE_COMMUNE,
                    'orderby' => ['p.name' => 'ASC']
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
