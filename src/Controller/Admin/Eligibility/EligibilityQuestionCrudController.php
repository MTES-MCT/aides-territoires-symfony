<?php

namespace App\Controller\Admin\Eligibility;

use App\Entity\Eligibility\EligibilityQuestion;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EligibilityQuestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EligibilityQuestion::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
