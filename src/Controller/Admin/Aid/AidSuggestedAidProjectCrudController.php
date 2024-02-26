<?php

namespace App\Controller\Admin\Aid;

use App\Entity\Aid\AidSuggestedAidProject;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AidSuggestedAidProjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AidSuggestedAidProject::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('aid', 'Aide')
        ->setFormTypeOption('attr', ['readonly' => true])
        ;
        yield TextField::new('creator', 'Auteur')
        ->setFormTypeOption('attr', ['readonly' => true])
        ;
        yield TextField::new('project', 'Projet')
        ->setFormTypeOption('attr', ['readonly' => true])
        ;
        yield BooleanField::new('isAssociated', 'Aide associée')
        ->setHelp('Cette aide a-t-elle été acceptée par le porteur du projet ?')
        ->onlyOnForms();
        yield DateField::new('timeAssociated', 'Date d’association')
        ->setHelp('Date à laquelle cette aide a été acceptée par le porteur du projet')
        ->onlyOnForms();
        yield BooleanField::new('isRejected', 'Aide rejetée')
        ->setHelp('Cette aide a-t-elle été rejetée par le porteur du projet ?')
        ->onlyOnForms();
        yield DateField::new('timeRejected', 'Date de rejet')
        ->setHelp('Date à laquelle cette aide a été rejetée par le porteur du projet')
        ->onlyOnForms();

        yield DateTimeField::new('dateCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->onlyOnForms();
    }
}
