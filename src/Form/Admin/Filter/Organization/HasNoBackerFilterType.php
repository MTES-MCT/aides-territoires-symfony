<?php

namespace App\Form\Admin\Filter\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HasNoBackerFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Pas de porteur associé'
        ]);
    }

    public function getParent(): ?string
    {
        return CheckboxType::class;
    }
}
