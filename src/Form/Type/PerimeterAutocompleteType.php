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
                    $query = str_replace(' ', '-', $query);
                    $qb
                    ->addSelect('MATCH_AGAINST(p.name) AGAINST(:name) AS HIDDEN relevance_score')
                    ->andWhere('MATCH_AGAINST(p.name) AGAINST(:name) > 0')
                    ->setParameter('name', $query);
            
                    // Pondérer les résultats avec une priorité pour les correspondances exactes
                    // On applique un boost aux résultats qui commencent par la recherche (comme 'saint affrique')
                    $qb->addSelect('CASE WHEN p.name LIKE :startMatch THEN 1 ELSE 0 END AS HIDDEN start_match')
                        ->setParameter('startMatch', $query . '%');
                
                    // Trier d'abord par les correspondances exactes (démarrant par la recherche)
                    // Ensuite, trier par score de pertinence
                    $qb->orderBy('start_match', 'DESC') // Les correspondances exactes en premier
                        ->addOrderBy('relevance_score', 'DESC'); // Puis, trier par score de pertinence
                    }
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
