<?php

namespace App\Form\Admin\Filter\Backer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HasNoOrganizationFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Pas de structure associée'
        ]);
    }

    public function getParent(): ?string
    {
        return CheckboxType::class;
    }
}
