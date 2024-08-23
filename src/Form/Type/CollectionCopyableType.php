<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CollectionCopyableType extends CollectionType
{
    public function getBlockPrefix(): string
    {
        return 'collection_copyable_type';
    }
}
