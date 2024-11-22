<?php

namespace App\Form\Project;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RemoveFromFavoriteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // extends type de base
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
