<?php

namespace App\Controller\Admin\Perimeter;

use App\Entity\Perimeter\PerimeterData;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PerimeterDataCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PerimeterData::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('prop', 'Nom propriété');
        yield TextField::new('value', 'Valeur propriété');
    }
}
