<?php

namespace App\Controller\Admin\Eligibility;

use App\Entity\Eligibility\EligibilityTestQuestion;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EligibilityTestQuestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EligibilityTestQuestion::class;
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
