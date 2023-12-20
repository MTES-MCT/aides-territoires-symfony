<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class TextLengthCountType extends TextType
{
    public function getBlockPrefix(): string
    {
        return 'text_length_count_type';
    }
}
