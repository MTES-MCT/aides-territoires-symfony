<?php

namespace App\Controller\Admin\DataSource;

use App\Controller\Admin\AtCrudController;
use App\Entity\DataSource\DataSource;
use App\Repository\User\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class DataSourceCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return DataSource::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextareaField::new('description', 'Description complète de la source de données')
        ->onlyOnForms();
        yield TextareaField::new('importDetails', 'Détails additionels concernant l\'import')
        ->onlyOnForms();
        yield AssociationField::new('backer', 'Porteur d\'aides')
        ->onlyOnForms()
        ->setFormTypeOption('choice_label', 'name');
        yield AssociationField::new('perimeter', 'Périmètre')
        ->onlyOnForms()
        ->autocomplete()
        ;
        yield UrlField::new('importApiUrl', 'URL de l\'API')
        ->onlyOnForms()
        ->setHelp('L\'URL utilisée par le script d\'import');
        yield UrlField::new('importDataUrl', 'URL d\'origine de la donnée importée')
        ->onlyOnForms();
        $choices = [];
        foreach (DataSource::LICENCES as $licence) {
            $choices[$licence['name']] = $licence['slug'];
        }
        yield ChoiceField::new('importLicence', 'Licence de la donnée importée')
        ->onlyOnForms()
        ->setChoices($choices);
        yield AssociationField::new('contactTeam', 'Contact (Team AT)')
        ->setFormTypeOption('query_builder', function (UserRepository $entityRepository) {
            return $entityRepository->getQueryBuilder(['onlyAdmin' => true]);
        });
        ;
        yield TextareaField::new('contactBacker', 'Contact(s) coté porteur')
        ->onlyOnForms();
        yield AssociationField::new('aidAuthor', 'L\'auteur par défaut des aides importées')
        ->autocomplete()
        ->setHelp('Mettre Admin AT (aides-territoires@beta.gouv.fr) par défaut');
        yield NumberField::new('nbAids', 'Nombre d\'aides')
        ->onlyOnIndex();
        yield DateField::new('timeLastAccess', 'Date du dernier accès')
        ->onlyOnIndex();
        
    }

    public function configureActions(Actions $actions): Actions
    {
        $analyse = Action::new('analyse', 'Analyser')
        ->setHtmlAttributes(['title' => 'Importer']) // titre
        // ->linkToCrudAction('import') // l'action appellée
        ->linkToRoute('admin_data_source_analyse', function (DataSource $entity) {
            return [
                'id' => $entity->getId()
            ];
        });
        return parent::configureActions($actions)
            // ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            // ->add(Crud::PAGE_INDEX, $analyse)
        ;
    }
}
