<?php

namespace App\Form\Admin\Filter\Aid;

use App\Entity\Backer\Backer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AidBackerFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Backer::class,
            'autocomplete' => true,
            'attr' => [
                'placeholder' => 'Choix porteur'
            ]
        ]);
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}