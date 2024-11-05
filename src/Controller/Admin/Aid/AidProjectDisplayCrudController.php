<?php

namespace App\Controller\Admin\Aid;

use App\Controller\Admin\AtCrudController;
use App\Entity\Aid\AidProject;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class AidProjectDisplayCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return AidProject::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('aid', 'Aide')
            ->setFormTypeOption('attr', ['readonly' => true])
            ->autocomplete();
        yield AssociationField::new('creator', 'Auteur association')
            ->autocomplete();
    }
}
