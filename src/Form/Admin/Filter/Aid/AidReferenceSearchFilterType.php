<?php

namespace App\Form\Admin\Filter\Aid;

use App\Entity\Backer\Backer;
use App\Entity\Reference\ProjectReference;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AidReferenceSearchFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => ProjectReference::class,
            'autocomplete' => true,
            'attr' => [
                'placeholder' => 'Choix projet référent'
            ]
        ]);
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}