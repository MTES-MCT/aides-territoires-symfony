<?php

namespace App\Controller\Admin\Project;

use App\Controller\Admin\AtCrudController;
use App\Entity\Project\ProjectValidated;
use App\Field\TrumbowygField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProjectValidatedCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectValidated::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('projectName', 'Nom');
        yield AssociationField::new('project', 'Projet lié')
            ->autocomplete()
            ->hideOnIndex();
        yield TrumbowygField::new('description', 'Description')
            ->hideOnIndex();
        yield TextField::new('aidName', 'Nom de l\'aide');
        yield AssociationField::new('aid', 'Aide liée')
            ->autocomplete()
            ->hideOnIndex();
        yield AssociationField::new('organization', 'Organisation porteuse')
            ->autocomplete();
        yield TextField::new('financerName', 'Nom du porteur de l’aide obtenue');
        yield AssociationField::new('financer', 'Porteur d’aides lié')
            ->autocomplete()
            ->hideOnIndex();
        yield IntegerField::new('budget', 'Budget définitif')
            ->hideOnIndex();
        yield IntegerField::new('amountObtained', 'Montant obtenu');
        yield DateTimeField::new('timeObtained', 'Date à laquelle l’aide a été obtenue par le porteur du projet')
            ->setHelp('Date à laquelle l’aide a été obtenue par le porteur du projet');
        yield DateTimeField::new('timeCreate', 'Date de création')
            ->setFormTypeOption('attr', ['readonly' => true])
            ->hideOnIndex();
    }
}
