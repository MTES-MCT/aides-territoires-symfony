<?php

namespace App\Controller\Admin\Reference;

use App\Controller\Admin\AtCrudController;
use App\Entity\Reference\ProjectReferenceCategory;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProjectReferenceCategoryCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectReferenceCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
    }
}