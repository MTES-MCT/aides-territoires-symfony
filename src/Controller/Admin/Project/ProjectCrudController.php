<?php

namespace App\Controller\Admin\Project;

use App\Controller\Admin\Aid\AidProjectDisplayCrudController;
use App\Controller\Admin\AtCrudController;
use App\Entity\Project\Project;
use App\Field\TrumbowygField;
use App\Field\VichImageField;
use App\Repository\Keyword\KeywordSynonymlistRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProjectCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                Project::STATUS_DRAFT => Project::STATUS_DRAFT,
                Project::STATUS_REVIEWABLE => Project::STATUS_REVIEWABLE,
                Project::STATUS_PUBLISHED => Project::STATUS_PUBLISHED
            ]))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $stepChoices = [];
        foreach (Project::PROJECT_STEPS as $step) {
            $stepChoices[$step['name']] = $step['slug'];
        }

        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
        ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
        ->setHelp('Laisser vide pour autoremplir.')
        ->hideOnIndex()
        ;
        yield AssociationField::new('projectReference', 'Projet référent')
        ;
        yield BooleanField::new('referentNotFound', 'Référent non trouvé')
        ->hideOnIndex();

        yield TrumbowygField::new('description', 'Description')
        ->hideOnIndex();
        yield TrumbowygField::new('privateDescription', 'Notes internes du projet')
        ->hideOnIndex();

        yield VichImageField::new('imageFile', 'Image')
        ->setHelp('Évitez les fichiers trop lourds.')
        ->hideOnIndex()
        ;
        yield DateField::new('dueDate', 'Date d’échéance')
        ->hideOnIndex();
        yield ChoiceField::new('step', 'Avancement du projet')
        ->setChoices($stepChoices)
        ->hideOnIndex();
        yield IntegerField::new('budget', 'Budget prévisionnel')
        ->hideOnIndex();
        yield TextareaField::new('keyWords', 'Mots-clés')
        ->setHelp('mots-clés associés au projet')
        ->hideOnIndex();
        yield AssociationField::new('author', 'Auteur')
        ->autocomplete()
        ->hideOnIndex();
        yield AssociationField::new('organization', 'Structure')
        ->autocomplete()
        ->hideOnIndex();
        yield TextField::new('otherProjectOwner', 'Autre maître d’ouvrage')
        ->hideOnIndex();
        yield IntegerField::new('nbAids', 'Nombre d\'aides')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideOnIndex();
        yield CollectionField::new('aidProjects', 'Aides associées')
        ->setEntryIsComplex()
        ->useEntryCrudForm(AidProjectDisplayCrudController::class)
        ->allowDelete(false)
        ->allowAdd(false)
        ->hideOnIndex()
        ;
        yield BooleanField::new('isPublic', 'Projet public');
        
        $contractLinksChoices = [];
        foreach (Project::CONTRACT_LINK as $contractLink) {
            $contractLinksChoices[$contractLink['name']] = $contractLink['slug'];
        }
        yield ChoiceField::new('contractLink', 'Appartenance à un plan/programme/contrat')
        ->setChoices($contractLinksChoices)
        ->hideOnIndex();
        yield AssociationField::new('keywordSynonymlists', 'Types de projet')
        ->setFormTypeOptions([
            'query_builder' => function (KeywordSynonymlistRepository $er) {
                return $er->getQueryBuilder([
                    'orderBy' => [
                        'sort' => 'ks.name',
                        'order' => 'ASC'
                    ]
                ]);
            },
        ])
        ->hideOnIndex();
        yield TextField::new('projectTypesSuggestion', 'Type de projet suggéré')
        ->hideOnIndex();

        $statusChoices = [];
        foreach (Project::STATUS as $status) {
            $statusChoices[$status['name']] = $status['slug'];
        }
        yield ChoiceField::new('status', 'Statut')
        ->setChoices($statusChoices)
        ;
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideWhenCreating();

    }

    public function configureActions(Actions $actions): Actions
    {
        // action pour afficher le qrCode
        $displayOnFront = Action::new('displayOnFront', 'Afficher sur le site', 'far fa-eye')
            ->setHtmlAttributes(['title' => 'Afficher sur le site', 'target' => '_blank']) // titre
            ->displayIf(fn ($entity) => $entity->isIsPublic() ?? false); // condition d'affichage
            ;

        //set the link using a string or a callable (function like its being used here)
        $displayOnFront->linkToUrl(function($entity) {
            return $this->generateUrl('app_project_project_public_details', ['id' => $entity->getId(), 'slug' => $entity->getSlug()]);
        });
        
        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->add(Crud::PAGE_EDIT, $displayOnFront)
        ;
    }
}
