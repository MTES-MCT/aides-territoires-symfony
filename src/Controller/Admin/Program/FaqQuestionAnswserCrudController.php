<?php

namespace App\Controller\Admin\Program;

use App\Controller\Admin\AtCrudController;
use App\Entity\Page\FaqQuestionAnswser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FaqQuestionAnswserCrudController extends AtCrudController
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
        yield TextEditorField::new('answer', 'Réponse')
        ->setColumns(12);
        yield AssociationField::new('faqCategory', 'Catégorie FAQ');
        yield IntegerField::new('position', 'Position')->onlyOnIndex();
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->onlyWhenUpdating();
        yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->onlyWhenUpdating();
    }
}
