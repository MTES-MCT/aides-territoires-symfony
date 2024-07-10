<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class DisplayContentType extends TextType
{
    public function getBlockPrefix(): string
    {
        return 'display_content_type';
    }
}
