<?php

namespace App\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EntityCheckboxAbsoluteType extends EntityType
{
    public function getBlockPrefix(): string
    {
        return 'entity_checkbox_absolute_type';
    }
}
