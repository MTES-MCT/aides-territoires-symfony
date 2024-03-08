<?php

namespace App\Controller\Admin\Program;

use App\Controller\Admin\AtCrudController;
use App\Entity\Page\FaqCategory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FaqCategoryCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaqCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield AssociationField::new('faq', 'FAQ');
        yield IntegerField::new('position', 'Position')->onlyOnIndex();
        yield CollectionField::new('faqQuestionAnswsers', 'Questions/Réponses')
        ->setEntryIsComplex(true)
        ->useEntryCrudForm(FaqQuestionAnswserCollectionCrudController::class)
        ;
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->onlyWhenUpdating();
        yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->onlyWhenUpdating();
    }
}
