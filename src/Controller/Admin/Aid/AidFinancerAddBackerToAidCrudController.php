<?php

namespace App\Controller\Admin\Aid;

use App\Controller\Admin\AtCrudController;
use App\Entity\Aid\AidFinancer;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class AidFinancerAddBackerToAidCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return AidFinancer::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('backer', 'Porteur d\'aide')
            ->autocomplete();
    }
}
