<?php

namespace App\Controller\Admin\Category;

use App\Controller\Admin\AtCrudController;
use App\Entity\Category\CategoryTheme;
use App\Field\TrumbowygField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryThemeCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return CategoryTheme::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Laisser vide pour autoremplir.')
        ;
        yield TrumbowygField::new('shortDescription', 'Description courte')
        ->onlyOnForms();
    }
}
