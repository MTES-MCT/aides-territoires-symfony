<?php

namespace App\Controller\Admin\Page;

use App\Controller\Admin\AtCrudController;
use App\Entity\Page\Page;
use App\Field\TrumbowygField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PageLinkSearchPageCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('url', 'Url')
        ;
        yield TextField::new('name', 'Titre');
        yield TrumbowygField::new('description', 'Contenu')
        ;

    }
}
