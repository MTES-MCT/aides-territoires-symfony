<?php

namespace App\Controller\Admin\Backer;

use App\Controller\Admin\AtCrudController;
use App\Entity\Backer\BackerGroup;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BackerGroupCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return BackerGroup::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Laisser vide pour autoremplir.');
        yield AssociationField::new('backerSubCategory', 'Sous-Catégorie')
            ->setFormTypeOption('choice_label', 'name');
    }
}
