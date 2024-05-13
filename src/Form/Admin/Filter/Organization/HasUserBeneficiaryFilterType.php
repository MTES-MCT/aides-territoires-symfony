<?php

namespace App\Form\Admin\Filter\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HasUserBeneficiaryFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Utilisateur bénéficiaire',
            'choices' => [
                'A au moins 1 bénéficiaire' => true,
                'N\'a pas de bénéficiaire' => false,
            ],
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
