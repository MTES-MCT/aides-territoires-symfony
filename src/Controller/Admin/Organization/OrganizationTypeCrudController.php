<?php

namespace App\Controller\Admin\Organization;

use App\Controller\Admin\AtCrudController;
use App\Entity\Organization\OrganizationType;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrganizationTypeCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrganizationType::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Laisser vide pour autoremplir.')
        ;
        yield AssociationField::new('organizationTypeGroup', 'Groupe')
        ->setFormTypeOption('choice_label', 'name');
    }
}
