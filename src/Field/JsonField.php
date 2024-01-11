<?php
namespace App\Field;

use App\Form\Type\JsonCodeEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class JsonField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = 'jsonField'): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            // this template is used in 'index' and 'detail' pages
            // ->setTemplatePath('admin/field/vich_image.html.twig')
            // ->setTemplatePath('admin/field/text-length-count-field.html.twig')
            // this is used in 'edit' and 'new' pages to edit the field contents
            // you can use your own form types too
            ->setFormType(JsonCodeEditorType::class)
            // ->setFormTypeOption('attr', ['class' => 'trumbowyg'])
            ->setDefaultColumns('col-md-6 col-xxl-5')
            
        ;
    }
}