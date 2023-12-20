<?php

namespace App\Controller\Admin\Keyword;

use App\Controller\Admin\AtCrudController;
use App\Entity\Keyword\KeywordSynonymlist;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class KeywordSynonymlistCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return KeywordSynonymlist::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Laisser vide pour autoremplir.')
        ;
        yield TextareaField::new('keywordsList', 'Liste de mots clés')
        ->setHelp('La liste de mots clés correspond à une liste de synonymes, et termes du champ lexical associé.')
        ;
    }
}
