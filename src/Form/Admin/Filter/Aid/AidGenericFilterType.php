<?php

namespace App\Form\Admin\Filter\Aid;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AidGenericFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'Aides génériques' => 'generic',
                'Local aids' => 'local',
                'Standard aids' => 'standard',
            ],
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}