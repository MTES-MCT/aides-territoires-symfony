<?php

namespace App\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EntityCheckboxGroupAbsoluteType extends EntityType
{
    public function  getBlockPrefix(): string
    {
        return 'entity_checkbox_group_absolute_type';
    }
}
