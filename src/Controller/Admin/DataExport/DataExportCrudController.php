<?php

namespace App\Controller\Admin\DataExport;

use App\Controller\Admin\AtCrudController;
use App\Entity\DataExport\DataExport;
use App\Field\UrlClickField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DataExportCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return DataExport::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield UrlClickField::new('urlExportedFile', 'Fichier exporté')
        ->setFormTypeOption('attr', ['readonly' => true]);
        yield TextField::new('author', 'Auteur')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
        ;
        yield DateTimeField::new('timeCreate', 'Date création')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // action pour afficher le qrCode
        $displayOnFront = Action::new('downloadFile', 'Télécharger', 'fas fa-download')
            ->setHtmlAttributes(['title' => 'Télécharger', 'target' => '_blank']) // titre
            ;

        //set the link using a string or a callable (function like its being used here)
        $displayOnFront->linkToUrl(function($entity) {
            return $entity->getUrlExportedFile();
        });
        
        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
        ;
    }
}
