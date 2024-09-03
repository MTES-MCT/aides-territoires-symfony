<?php

namespace App\Controller\Admin\Backer;

use App\Controller\Admin\AtCrudController;
use App\Entity\Backer\BackerCategory;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BackerCategoryCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return BackerCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Laisser vide pour autoremplir.');
    }
}
