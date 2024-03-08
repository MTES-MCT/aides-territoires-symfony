<?php

namespace App\Controller\Admin\Program;

use App\Controller\Admin\AtCrudController;
use App\Entity\Page\FaqQuestionAnswser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FaqQuestionAnswserCollectionCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaqQuestionAnswser::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('faqCategory')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('question', 'Question')
        ->setColumns(12);
        yield TextareaField::new('answer', 'Réponse')
        ->setColumns(12);
    }
}
