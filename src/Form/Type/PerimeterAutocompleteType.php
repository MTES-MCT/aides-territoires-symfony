<?php

namespace App\Form\Type;

use App\Entity\Perimeter\Perimeter;
use App\Repository\Perimeter\PerimeterRepository;
use App\Service\Perimeter\PerimeterService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class PerimeterAutocompleteType extends AbstractType
{
    public function __construct(
        protected PerimeterService $perimeterService
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Perimeter::class,
            'placeholder' => 'Tous les territoires',
            'choice_label' => function ($entity) {
                return $this->perimeterService->getSmartName($entity);
            },
            'data_class' => null,
            'preload' => false,
            'query_builder' => function (PerimeterRepository $perimeterRepository) {
                return $perimeterRepository->getQueryBuilder([
                    'isVisibleToUsers' => true,
                    'scaleLowerThan' => Perimeter::SCALE_CONTINENT
                ]);
            },
            'filter_query' => function (QueryBuilder $qb, string $query, PerimeterRepository $repository) {
                if (!$query) {
                    return;
                }

                // c'est un code postal
                if (preg_match('/^[0-9]{5}$/', $query)) {
                    $qb
                        ->andWhere('
                        p.zipcodes LIKE :zipcodes
                    ')
                        ->setParameter('zipcodes', '%' . $query . '%');
                    ;
                } else { // c'est une string
                    $query = str_replace('-', ' ', $query);
                    $qb
                    ->addSelect('MATCH_AGAINST(p.name) AGAINST(:name) AS HIDDEN relevance_score')
                    ->andWhere('MATCH_AGAINST(p.name) AGAINST(:name) > 0')
                    ->setParameter('name', $query)
                    ->orderBy('relevance_score', 'DESC');
                }
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
