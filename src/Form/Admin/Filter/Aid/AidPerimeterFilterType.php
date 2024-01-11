<?php

namespace App\Form\Admin\Filter\Aid;

use App\Entity\Perimeter\Perimeter;
use App\Form\Type\PerimeterAutocompleteType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField] 
class AidPerimeterFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Perimeter::class,
            'autocomplete' => true,
            'attr' => [
                'placeholder' => 'Choix périmètre'
            ]
        ]);
    }

    public function getParent()
    {
        return PerimeterAutocompleteType::class;
    }
}