<?php

namespace App\Form\Admin\Filter\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailDomainFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'placeholder' => 'Domaine email',
            ],
            'help' => 'exemple : @beta.gouv.fr'
        ]);
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
