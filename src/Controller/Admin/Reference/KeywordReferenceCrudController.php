<?php

namespace App\Controller\Admin\Reference;

use App\Controller\Admin\AtCrudController;
use App\Controller\Admin\Filter\KeywordReferenceParentFilter;
use App\Entity\Reference\KeywordReference;
use App\Form\Reference\KeywordReferenceCollectionType;
use App\Form\Reference\KeywordReferenceEditType;
use Aws\Crypto\Polyfill\Key;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

class KeywordReferenceCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return KeywordReference::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(KeywordReferenceParentFilter::new('parentFilter'))
            ->add('parent')

            // most of the times there is no need to define the
            // filter type because EasyAdmin can guess it automatically
            // ->add(BooleanFilter::new('published'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield AssociationField::new('parent', 'Parent');
        yield CollectionField::new('keywordReferences', 'Synonymes')
        ->setEntryType(KeywordReferenceEditType::class)
        ;
        yield BooleanField::new('intention', 'Intention');
    }
}
