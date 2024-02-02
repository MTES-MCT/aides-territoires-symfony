<?php

namespace App\Form\Admin\Filter\Aid;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AidStateFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'Actuellement affichées' => 'showInSearch',
                'Expirent bientôt' => 'deadline',
                'Aides expirées' => 'expired',
                'Actuellement non affichées' => 'hidden',
                
            ],
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}