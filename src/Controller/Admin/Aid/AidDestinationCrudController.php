<?php

namespace App\Controller\Admin\Aid;

use App\Controller\Admin\AtCrudController;
use App\Entity\Aid\AidDestination;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AidDestinationCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return AidDestination::class;
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
