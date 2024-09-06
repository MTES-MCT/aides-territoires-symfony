<?php

namespace App\Controller\Admin\Keyword;

use App\Controller\Admin\AtCrudController;
use App\Entity\Keyword\Keyword;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class KeywordCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Keyword::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Laisser vide pour autoremplir.');
    }
}
