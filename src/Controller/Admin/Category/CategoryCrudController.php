<?php

namespace App\Controller\Admin\Category;

use App\Controller\Admin\AtCrudController;
use App\Entity\Category\Category;
use App\Field\TrumbowygField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
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
        yield AssociationField::new('categoryTheme', 'Thème de la catégorie')
        ->setFormTypeOption('choice_label', 'name')
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Sous-catégories')
            ->setPageTitle('new', 'Créer une sous-catégorie')
            ->setPageTitle('edit', 'Modifier une sous-catégorie')
            ->setPageTitle('detail', 'Détails de sous-catégorie')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Créer sous-catégorie');
            })
        ;
    }
}
