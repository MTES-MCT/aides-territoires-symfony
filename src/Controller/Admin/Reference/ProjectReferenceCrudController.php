<?php

namespace App\Controller\Admin\Reference;

use App\Controller\Admin\AtCrudController;
use App\Entity\Reference\ProjectReference;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProjectReferenceCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectReference::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
        ->hideOnIndex()
        ->setFormTypeOption('attr', ['readonly' => true]);
        yield AssociationField::new('projectReferenceCategory', 'Catégorie');
        yield AssociationField::new('excludedKeywordReferences', 'Mots clés exclus');
    }
}
