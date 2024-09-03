<?php

namespace App\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EntityGroupedType extends EntityType
{
    public function getBlockPrefix(): string
    {
        return 'entity_grouped_type';
    }
}
