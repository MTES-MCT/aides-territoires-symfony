<?php

namespace App\Controller\Admin\Program;

use App\Controller\Admin\AtCrudController;
use App\Entity\Page\FaqCategory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FaqCategoryCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaqCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield AssociationField::new('faq', 'FAQ');
        yield IntegerField::new('position', 'Position')->onlyOnIndex();
    }
}
