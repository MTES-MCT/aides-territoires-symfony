<?php

namespace App\Field;

use App\Form\Type\CollectionCopyableType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Contracts\Translation\TranslatableInterface;

final class CollectionCopyableField implements FieldInterface
{
    use FieldTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            // ->setTemplateName('crud/field/array')
            ->setFormType(CollectionCopyableType::class)
            ->addCssClass('field-array')
            // ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-collection.js')->onlyOnForms())
            ->setDefaultColumns('col-md-7 col-xxl-6');
    }
}
