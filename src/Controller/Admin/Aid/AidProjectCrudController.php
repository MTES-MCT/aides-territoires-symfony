<?php

namespace App\Controller\Admin\Aid;

use App\Controller\Admin\AtCrudController;
use App\Entity\Aid\AidProject;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AidProjectCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return AidProject::class;
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
        yield BooleanField::new('aidRequested', 'Aide demandée')
        ->setHelp('Cette aide a-t-elle été demandée par le porteur du projet ?')
        ->onlyOnForms();
        yield DateField::new('timeRequested', 'Date de la demande')
        ->setHelp('Date à laquelle cette aide a été demandée par le porteur du projet')
        ->onlyOnForms();
        yield BooleanField::new('aidObtained', 'Aide obtenue')
        ->setHelp('Cette aide a-t-elle été obtenue par le porteur du projet ?')
        ->onlyOnForms();
        yield DateField::new('timeObtained', 'Date de l’obtention')
        ->setHelp('Date à laquelle cette aide a été obtenue par le porteur du projet')
        ->onlyOnForms();
        yield BooleanField::new('aidDenied', 'Aide refusée')
        ->setHelp('Cette aide a-t-elle été refusée au porteur du projet ?')
        ->onlyOnForms();
        yield DateField::new('timeDenied', 'Date du refus')
        ->setHelp('Date à laquelle cette aide a été refusée au porteur du projet')
        ->onlyOnForms();
        yield BooleanField::new('aidPaid', 'Aide reçue')
        ->setHelp('Cette aide a-t-elle été reçue par le porteur du projet ?')
        ->onlyOnForms();
        yield DateField::new('timePaid', 'Date de la réception de l’aide')
        ->setHelp('Date à laquelle cette aide a été reçue par le porteur du projet')
        ->onlyOnForms();
    }
}
