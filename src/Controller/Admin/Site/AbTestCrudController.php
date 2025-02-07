<?php

namespace App\Controller\Admin\Site;

use App\Controller\Admin\AtCrudController;
use App\Entity\Site\AbTest;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AbTestCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return AbTest::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name')->setLabel('Nom');
        yield DateField::new('dateStart')->setLabel('Date de début');
        yield DateField::new('dateEnd')->setLabel('Date de fin');
    }
}
