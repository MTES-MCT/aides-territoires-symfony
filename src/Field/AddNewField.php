<?php

namespace App\Field;

use App\Form\Type\AddNewType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

final class AddNewField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = 'Url'): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(AddNewType::class)
        ;
    }
}