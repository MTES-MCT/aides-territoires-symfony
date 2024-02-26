<?php

namespace App\Controller\Admin\Eligibility;

use App\Entity\Eligibility\EligibilityTestQuestion;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EligibilityTestQuestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EligibilityTestQuestion::class;
    }
}
