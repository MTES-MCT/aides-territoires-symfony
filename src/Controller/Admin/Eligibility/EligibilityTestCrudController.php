<?php

namespace App\Controller\Admin\Eligibility;

use App\Entity\Eligibility\EligibilityTest;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EligibilityTestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EligibilityTest::class;
    }
}
