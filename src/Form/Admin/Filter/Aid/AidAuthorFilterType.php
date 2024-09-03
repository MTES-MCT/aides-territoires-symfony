<?php

namespace App\Form\Admin\Filter\Aid;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AidAuthorFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'placeholder' => 'Email, nom ou prénom auteur',
            ],
        ]);
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
