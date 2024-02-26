<?php

namespace App\Controller\Admin\Perimeter;

use App\Entity\Perimeter\FinancialData;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FinancialDataCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FinancialData::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('perimeter', 'Périmètre')
        ->autocomplete();
        yield TextField::new('inseeCode', 'Code INSEE')
        ->onlyOnForms();
        yield IntegerField::new('Year', 'Exercice');
        yield IntegerField::new('populationStrata', 'Strate population')
        ->onlyOnForms();
        yield TextField::new('aggregate', 'Agrégat');
        yield NumberField::new('mainBudgetAmount', 'Montant budget principal')
        ->setHelp('Valeur de l’agrégat pour le budget principal');
        yield IntegerField::new('displayOrder', 'Ordre d’affichage')
        ->onlyOnForms()
        ->setHelp('Variable interne OFGL pour data visualisation');

    }
}
