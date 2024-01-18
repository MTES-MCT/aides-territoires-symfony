<?php

namespace App\Controller\Admin\Program;

use App\Controller\Admin\AtCrudController;
use App\Entity\Program\PageTab;
use App\Field\TrumbowygField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PageTabCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return PageTab::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('program', 'Program')
        ->autocomplete()
        ->setHelp('Programme lié à cette page.');
        yield TextField::new('name', 'Nom');
        yield TrumbowygField::new('description', 'Contenu')
        ->hideOnIndex();
        
        yield FormField::addFieldset('A propos de cet onglet');
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->onlyWhenUpdating();
        yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->onlyWhenUpdating();
    }
}
