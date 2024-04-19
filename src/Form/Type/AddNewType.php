<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddNewType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'add_new_type';
    }
}
