<?php

namespace App\Controller\Admin\Organization;

use App\Controller\Admin\AtCrudController;
use App\Entity\Organization\Organization;
use App\Entity\Perimeter\Perimeter;
use App\Repository\Perimeter\PerimeterRepository;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrganizationCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Organization::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Général');
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Laisser vide pour autoremplir.')
        ;
        yield AssociationField::new('organizationType', 'Type')
        ->setFormTypeOption('choice_label', 'name');

        yield CollectionField::new('organizationAccesses', 'Membres')
        ->onlyOnForms()
        ->setEntryIsComplex()
        ->useEntryCrudForm(OrganizationAccessCrudCollectionFromOrganizationController::class)
        ;

        yield AssociationField::new('backer', 'Porteur d\'aide')
        ->autocomplete()
        ->onlyOnForms();
        yield TextField::new('intercommunalityType', 'Type d\'inter-communalité')
        ->onlyOnForms();
        yield TextField::new('densityTypology', 'Typologie de densité')
        ->onlyOnForms();
        yield TextField::new('populationStrata', 'Strates de population')
        ->onlyOnForms();

        yield FormField::addFieldset('Projets');
        yield AssociationField::new('favoriteProjects', 'Projets Favoris')
        ->autocomplete()
        ->onlyOnForms();
        yield AssociationField::new('projects', 'Projets')
        ->autocomplete()
        ->onlyOnForms();
        yield AssociationField::new('projectValidateds', 'Projets subventionnés')
        ->autocomplete()
        ->onlyOnForms();

        yield FormField::addFieldset('Emplacement');
        yield TextField::new('address', 'Adresse')
        ->onlyOnForms();
        yield TextField::new('cityName', 'Ville')
        ->onlyOnForms();
        yield TextField::new('zipCode', 'Code postal')
        ->onlyOnForms();
        yield AssociationField::new('perimeter', 'Périmètre')
        ->autocomplete()
        ->onlyOnForms();
        yield AssociationField::new('perimeterDepartment', 'Département')
        ->setFormTypeOptions([
            'query_builder' => function (PerimeterRepository $er) {
                return $er->getQueryBuilder([
                    'scale' => Perimeter::SCALE_COUNTY,
                    'orderBy' => [
                        'sort' => 'p.code',
                        'order' => 'ASC'
                    ]
                ]);
            },
        ])
        ->onlyOnForms();
        yield AssociationField::new('perimeterRegion', 'Région')
        ->setFormTypeOptions([
            'query_builder' => function (PerimeterRepository $er) {
                return $er->getQueryBuilder([
                    'scale' => Perimeter::SCALE_REGION,
                    'orderBy' => [
                        'sort' => 'p.code',
                        'order' => 'ASC'
                    ]
                ]);
            },
        ])
        ->onlyOnForms();

        yield FormField::addFieldset('Informations légales');
        yield TextField::new('sirenCode', 'Code SIREN')
        ->onlyOnForms();
        yield TextField::new('siretCode', 'Code SIRET')
        ->onlyOnForms();
        yield TextField::new('apeCode', 'Code APE')
        ->onlyOnForms();
        yield TextField::new('inseeCode', 'Code INSEE')
        ->onlyOnForms();


        yield FormField::addFieldset('Composition du périmètre');
        yield IntegerField::new('inhabitantsNumber', 'Nombre d\'habitants')
        ->onlyOnForms();
        yield IntegerField::new('votersNumber', 'Nombre de votants')
        ->onlyOnForms();
        yield IntegerField::new('corporatesNumber', 'Nombre de sociétés')
        ->onlyOnForms();
        yield IntegerField::new('associationsNumber', 'Nombre d\'associations')
        ->onlyOnForms();
        yield IntegerField::new('municipalRoads', 'Nombre de routes municipales')
        ->onlyOnForms();
        yield IntegerField::new('departmentalRoads', 'Nombre de routes départementales')
        ->onlyOnForms();
        yield IntegerField::new('theaterNumber', 'Nombre de théatres')
        ->onlyOnForms();
        yield IntegerField::new('museumNumber', 'Nombre de musées')
        ->onlyOnForms();
        yield IntegerField::new('kindergartenNumber', 'Nombre de jardins d\'enfants')
        ->onlyOnForms();
        yield IntegerField::new('primarySchoolNumber', 'Nombre d\'écoles primaoires')
        ->onlyOnForms();
        yield IntegerField::new('middleSchoolNumber', 'Nombre de collèges')
        ->onlyOnForms();
        yield IntegerField::new('highSchoolNumber', 'Nombre de lycées')
        ->onlyOnForms();
        yield IntegerField::new('universityNumber', 'Nombre d\'universitées')
        ->onlyOnForms();
        yield IntegerField::new('swimmingPoolNumber', 'Nombre de piscines')
        ->onlyOnForms();
        yield IntegerField::new('placeOfWorshipNumber', 'Nombre de lieux de cultes')
        ->onlyOnForms();
        yield IntegerField::new('cemeteryNumber', 'Nombre de cimetières')
        ->onlyOnForms();
        yield IntegerField::new('bridgeNumber', 'Nombre de ponts')
        ->onlyOnForms();
        yield IntegerField::new('cinemaNumber', 'Nombre de cinéma')
        ->onlyOnForms();
        yield IntegerField::new('coveredSportingComplexNumber', 'Nombre de complexes sportifs couverts')
        ->onlyOnForms();
        yield IntegerField::new('footballFieldNumber', 'Nombre de terrains de footbal')
        ->onlyOnForms();
        yield IntegerField::new('forestNumber', 'Nombre de forêts')
        ->onlyOnForms();
        yield IntegerField::new('nurseryNumber', 'Nombre de garderies')
        ->onlyOnForms();
        yield IntegerField::new('otherOutsideStructureNumber', 'Nombre de structures extérieures autres')
        ->onlyOnForms();
        yield IntegerField::new('protectedMonumentNumber', 'Nombre de monuments protégés')
        ->onlyOnForms();
        yield IntegerField::new('recCenterNumber', 'Nombre de centres de loisirs')
        ->onlyOnForms();
        yield IntegerField::new('runningTrackNumber', 'Nombre de pistes de courses')
        ->onlyOnForms();
        yield IntegerField::new('shopsNumber', 'Nombre de commerces')
        ->onlyOnForms();
        yield IntegerField::new('tennisCourtNumber', 'Nombre de courts de tennis')
        ->onlyOnForms();


        yield FormField::addFieldset('Import');
        yield DateTimeField::new('importedTime', 'Date d\'import')
        ->setFormTypeOption(
            'attr', [
                'readonly' => true
            ]
        )
        ->onlyOnForms();
        yield BooleanField::new('isImported', 'Est importé')
        ->setFormTypeOption(
            'attr', [
                'readonly' => true
            ]
        )
        ->onlyOnForms();
    }
}
