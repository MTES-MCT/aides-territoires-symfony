<?php

namespace App\Form\Admin\Reference;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KeywordReferenceParentFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'Oui' => true,
                'Non' => false,
                // ...
            ],
            'mapped' => false
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}