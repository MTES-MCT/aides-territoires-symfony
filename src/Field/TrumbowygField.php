<?php

namespace App\Field;

use App\Form\MetaDataType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class TrumbowygField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = 'Image'): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            // this template is used in 'index' and 'detail' pages
            // ->setTemplatePath('admin/field/vich_image.html.twig')
            // this is used in 'edit' and 'new' pages to edit the field contents
            // you can use your own form types too
            ->setFormType(TextareaType::class)
            ->setFormTypeOption('attr', ['class' => 'trumbowyg'])
        ;
    }
}
