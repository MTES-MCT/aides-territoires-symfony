<?php

namespace App\Controller\Admin\Blog;

use App\Controller\Admin\AtCrudController;
use App\Entity\Blog\BlogPostCategory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BlogPostCategoryCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPostCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom de la catégorie');
        yield TextField::new('slug', 'Fragment d’URL')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Laisser vide pour autoremplir.')
        ;
        yield TextEditorField::new('description', 'Description');
    }
}
