<?php

namespace App\Form\Admin\Filter\Reference;

use App\Entity\Aid\Aid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField()]
class KeywordReferenceSuggestedAidFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Aid::class,
            'autocomplete' => true,
            'preload' => false,
            'attr' => [
                'placeholder' => 'Choix aide'
            ]
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
