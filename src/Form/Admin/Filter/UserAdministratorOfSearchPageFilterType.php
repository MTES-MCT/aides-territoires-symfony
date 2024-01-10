<?php

namespace App\Form\Admin\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAdministratorOfSearchPageFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'Oui' => 1,
                'Non' => 0,
            ],
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}