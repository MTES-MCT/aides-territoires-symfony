<?php

namespace App\Controller\Admin\Program;

use App\Controller\Admin\AtCrudController;
use App\Entity\Page\Faq;
use App\Form\Admin\Faq\FaqEditType;
use App\Form\Admin\Program\FaqCategoryCollectionType;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FaqCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Faq::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom')
        ->setHelp('Utilisé uniquement pour l\'association dans l\'administration.');
        yield AssociationField::new('pageTab', 'Onglet lié');
        // yield CollectionField::new('faqCategories', 'Catégories des questions')
        // ->setEntryIsComplex(true)
        // ->useEntryCrudForm(FaqCategoryCollectionCrudController::class)
        // ->setColumns(12)
        // ;
        yield CollectionField::new('faqCategories', 'Catégories des questions')
        ->setEntryType(FaqCategoryCollectionType::class)
        ->setColumns(12)
        ;
        // yield DateTimeField::new('timeCreate', 'Date de création')
        // ->setFormTypeOption('attr', ['readonly' => true])
        // ->onlyWhenUpdating();
        // yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
        // ->setFormTypeOption('attr', ['readonly' => true])
        // ->onlyWhenUpdating();
    }
}
