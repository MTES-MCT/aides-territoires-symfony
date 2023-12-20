<?php

namespace App\Controller\Admin\Organization;

use App\Controller\Admin\AtCrudController;
use App\Entity\Organization\OrganizationTypeGroup;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrganizationTypeGroupCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrganizationTypeGroup::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
    }
}
