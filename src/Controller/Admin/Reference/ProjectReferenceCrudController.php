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
        yield AssociationField::new('excludedKeywordReferences', 'Mots clés exclus')
            ->setHelp('Permet d\'exclure des mots clés de la liste des synonymes utilisés pour la recherche. Ex: Pour le projet "Mise en place de la télémedecine" on peu exclure "place" (centre, bourg, ...)');
        yield AssociationField::new('requiredKeywordReferences', 'Mots clés requis')
            ->setHelp('Permet de forcer la présence de certains mots clés dans les données de l\'aide. Si plusieurs mots sont renseignés on cherchera l\'un d\'entre eux. Ex: Pour le projet "Changement des fenêtres/portes d’un bâtiment public" on peu forcer les termes "fenêtre" ou "porte". Attention, si aucun des termes n\'est trouvé, l\'aide ne sera pas proposée (sauf si elle est associée au projet référent).');
    }
}
