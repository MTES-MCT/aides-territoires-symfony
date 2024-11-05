<?php

namespace App\Controller\Admin\Backer;

use App\Controller\Admin\AtCrudController;
use App\Entity\Backer\BackerAskAssociate;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class BackerAskAssociateCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return BackerAskAssociate::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('accepted')
            ->add('refused')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextareaField::new('description', 'Description')
            ->formatValue(function ($value) {
                return html_entity_decode($value);
            });
        yield AssociationField::new('organization', 'Structure')
            ->autocomplete();
        yield AssociationField::new('backer', 'Porteur d\'aide')
            ->autocomplete();
        yield AssociationField::new('user', 'Utilisateur')
            ->autocomplete();
        yield DateTimeField::new('timeCreate', 'Date de création');
        yield BooleanField::new('accepted', 'Est accepté');
        yield BooleanField::new('refused', 'Est refusé');
        yield TextareaField::new('refusedDescription', 'Motif du refus')
            ->formatValue(function ($value) {
                return html_entity_decode($value);
            });
    }
}
