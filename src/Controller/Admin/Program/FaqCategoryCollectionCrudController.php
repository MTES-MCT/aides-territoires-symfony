<?php

namespace App\Controller\Admin\Program;

use App\Controller\Admin\AtCrudController;
use App\Entity\Page\FaqCategory;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FaqCategoryCollectionCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaqCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom')->setColumns(12);
        yield CollectionField::new('faqQuestionAnswsers', 'Questions/Réponses')
        ->setEntryIsComplex(true)
        ->useEntryCrudForm(FaqQuestionAnswserCollectionCrudController::class)
        ->setColumns(12)
        ;
    }
}
