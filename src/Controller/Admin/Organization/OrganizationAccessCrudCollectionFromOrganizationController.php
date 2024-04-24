<?php

namespace App\Controller\Admin\Organization;

use App\Entity\Organization\OrganizationAccess;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

class OrganizationAccessCrudCollectionFromOrganizationController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrganizationAccess::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('user', 'Utilisateur')
            ->autocomplete(true),
            BooleanField::new('administrator', 'Administrateur'),
            BooleanField::new('editAid', 'Edition Aide'),
            BooleanField::new('editPortal', 'Edition Portail'),
            BooleanField::new('editBacker', 'Edition Backer'),
            BooleanField::new('editProject', 'Edition Projet'),
        ];
    }
    
}
