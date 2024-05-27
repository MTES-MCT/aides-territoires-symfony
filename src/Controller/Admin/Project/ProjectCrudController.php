<?php

namespace App\Controller\Admin\Project;

use App\Controller\Admin\Aid\AidProjectDisplayCrudController;
use App\Controller\Admin\AtCrudController;
use App\Entity\Project\Project;
use App\Field\TrumbowygField;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProjectCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isPublic')
            ->add(ChoiceFilter::new('status')->setChoices([
                'Brouillon' => Project::STATUS_DRAFT,
                'En revue' => Project::STATUS_REVIEWABLE,
                'Publié' => Project::STATUS_PUBLISHED
            ]))
            ->add(ChoiceFilter::new('contractLink')->setChoices(Project::CONTRACT_LINK_BY_SLUG))
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

        yield ImageField::new('imageFile', 'Image')
        ->setHelp('Évitez les fichiers trop lourds. Préférez les fichiers SVG.')
        ->setUploadDir($this->fileService->getUploadTmpDirRelative())
        ->setBasePath($this->paramService->get('cloud_image_url'))
        ->setUploadedFileNamePattern(Project::FOLDER.'/[slug]-[timestamp].[extension]')
        ->setFormTypeOption('upload_new', function(UploadedFile $file, string $uploadDir, string $fileName) {
            $this->imageService->sendUploadedImageToCloud($file, Project::FOLDER, $fileName);
            $this->getContext()->getEntity()->getInstance()->setImage($fileName);
        })
        ->onlyOnForms()
        ;
        yield BooleanField::new('deleteImage', 'Supprimer le fichier actuel')
        ->onlyWhenUpdating();

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
            return $this->generateUrl('app_project_project_public_details', ['id' => $entity->getId(), 'slug' => $entity->getSlug(), UrlGeneratorInterface::ABSOLUTE_URL]);
        });
        
        $exportCsvAction = $this->getExportCsvAction();
        $exportXlsxAction = $this->getExportXlsxAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->add(Crud::PAGE_EDIT, $displayOnFront)
            ->add(Crud::PAGE_INDEX, $exportCsvAction)
            ->add(Crud::PAGE_INDEX, $exportXlsxAction)
        ;
    }
}
