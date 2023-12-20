<?php

namespace App\Controller\Admin\Log;

use App\Controller\Admin\AtCrudController;
use App\Entity\Log\LogUserLogin;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LogUserLoginCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return LogUserLogin::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('user', 'Utilisateur')
        ->setFormTypeOption('choice_label', 'email')
        ->setFormTypeOption('attr', ['readonly' => true]);
        yield DateTimeField::new('timeCreate', 'Date création')
        ->setFormTypeOption('attr', ['readonly' => true]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
        ;
    }
}
