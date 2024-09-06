<?php

namespace App\Controller\Admin\Site;

use App\Controller\Admin\AtCrudController;
use App\Entity\Site\UrlRedirect;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UrlRedirectCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return UrlRedirect::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('oldUrl')
            ->setLabel('Ancienne URL')
            ->setHelp('le / est obligatoire en début et fin d\'URL.<div class="alert alert-warning">Pour les aides il faut obligatoirement passer par l\'édition de l\'aide (modification du champ Slug).</div>');
        yield TextField::new('newUrl')
            ->setLabel('Nouvelle URL')
            ->setHelp('le / est obligatoire en début et fin d\'URL');
        yield DateTimeField::new('timeCreate')->onlyOnIndex()->setLabel('Date de création');
    }
}
