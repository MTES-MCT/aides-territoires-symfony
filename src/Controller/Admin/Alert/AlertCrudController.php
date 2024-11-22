<?php

namespace App\Controller\Admin\Alert;

use App\Controller\Admin\AtCrudController;
use App\Entity\Alert\Alert;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AlertCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Alert::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('email', 'Email');
        yield TextareaField::new('querystring', 'QueryString')
            ->setFormTypeOption('attr', ['class' => 'not-trumbowyg'])
            ->setHelp('Ne doit PAS contenir de HTML.');
        yield TextField::new('title', 'Titre alerte');
        yield ChoiceField::new('alertFrequency', 'Fréquence alerte')
            ->setChoices(
                [
                    'Quotidienne' => Alert::FREQUENCY_DAILY_SLUG,
                    'Hebdomadaire' => Alert::FREQUENCY_WEEKLY_SLUG
                ]
            );
        yield DateTimeField::new('timeLatestAlert', 'Dernière alerte')
            ->setFormat('dd-MM-yyyy HH:mm:ss')
            ->hideOnForm();
        yield DateTimeField::new('dateLatestAlert', 'Date dernière alerte')
            ->setFormat('dd-MM-yyyy')
            ->hideOnForm();
        yield TextField::new('source', 'Source');
    }
}
