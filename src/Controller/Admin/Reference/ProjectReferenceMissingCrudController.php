<?php

namespace App\Controller\Admin\Reference;

use App\Controller\Admin\AtCrudController;
use App\Entity\Reference\ProjectReferenceMissing;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProjectReferenceMissingCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectReferenceMissing::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom du projet manquant');
        yield AssociationField::new('aids', 'Aides')
        ->autocomplete();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des Projets Référents Manquants')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un Nouveau Projet Référent Manquant')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le Projet Référent Manquant')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détails du Projet Référent Manquant');
    }
}
