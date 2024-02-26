<?php

namespace App\Controller\Admin\Eligibility;

use App\Entity\Eligibility\EligibilityQuestion;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EligibilityQuestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EligibilityQuestion::class;
    }
}
