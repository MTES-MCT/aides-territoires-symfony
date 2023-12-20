<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class UrlClickType extends TextType
{
    public function getBlockPrefix(): string
    {
        return 'url_click_type';
    }
}
