<?php

namespace App\Form\Type;

use App\Entity\Backer\Backer;
use App\Repository\Backer\BackerRepository;
use App\Service\Perimeter\PerimeterService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class BackerAutocompleteType extends AbstractType
{
    public function __construct(
        protected PerimeterService $perimeterService,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Backer::class,
            'placeholder' => 'Tous les porteurs d\'aides',
            'choice_label' => 'name',
            'data_class' => null,
            'preload' => true,
            'query_builder' => function (BackerRepository $backerRepository) {
                return $backerRepository->getQueryBuilder([
                    'hasFinancedAids' => true,
                    'active' => true,
                    'orderBy' => [
                        'sort' => 'b.name',
                        'order' => 'ASC',
                    ],
                ]);
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
