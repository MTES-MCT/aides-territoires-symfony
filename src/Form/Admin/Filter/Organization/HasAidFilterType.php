<?php

namespace App\Form\Admin\Filter\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HasAidFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Aides',
            'choices' => [
                'A au moins 1 utilisateur avec au moins 1 aide' => true,
                'N\'a pas d\'aide' => false,
            ],
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
