<?php

namespace App\Field;

use App\Form\Type\UrlClickType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

final class UrlClickField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = 'Url'): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(UrlClickType::class)
        ;
    }
}
