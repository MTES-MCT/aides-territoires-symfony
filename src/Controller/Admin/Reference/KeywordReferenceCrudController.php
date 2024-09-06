<?php

namespace App\Controller\Admin\Reference;

use App\Controller\Admin\AtCrudController;
use App\Controller\Admin\Filter\KeywordReferenceParentFilter;
use App\Entity\Reference\KeywordReference;
use App\Form\Reference\KeywordReferenceEditType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class KeywordReferenceCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return KeywordReference::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(KeywordReferenceParentFilter::new('parentFilter')->setFormTypeOption('mapped', false))
            ->add('parent')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield AssociationField::new('parent', 'Parent');
        yield CollectionField::new('keywordReferences', 'Synonymes')
            ->setEntryType(KeywordReferenceEditType::class);
        yield BooleanField::new('intention', 'Intention');
    }
}
