<?php

namespace App\Controller\Admin\Blog;

use App\Controller\Admin\AtCrudController;
use App\Entity\Blog\BlogPost;
use App\Field\TrumbowygField;
use App\Field\VichImageField;
use App\Repository\User\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BlogPostCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort(['id' => 'DESC']) // modifie le tri
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Article');
        yield IdField::new('id')
        ->onlyOnIndex();
        yield TextField::new('name', 'Titre de l\'article');
        yield TextField::new('slug', 'Fragment d’URL')
        ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
        ->setHelp('Laisser vide pour autoremplir.')
        ->onlyOnForms()
        ;
        yield AssociationField::new('blogPostCategory', 'Catégorie')
        ->setFormTypeOption('choice_label', 'name')
        ;


        yield AssociationField::new('user', 'Auteur')
        ->onlyOnForms()
        ->setFormTypeOptions([
            'query_builder' => function (UserRepository $er) {
                return $er->getQueryBuilder([
                    'onlyAdmin' => true
                ]);
            },
        ])
        ;

        yield ImageField::new('logoFile', 'Image de l\'article')
        ->setUploadDir($this->fileService->getUploadTmpDirRelative())
        ->setBasePath($this->paramService->get('cloud_image_url'))
        ->setUploadedFileNamePattern(BlogPost::FOLDER.'/[slug]-[timestamp].[extension]')
        ->setFormTypeOption('upload_new', function(UploadedFile $file, string $uploadDir, string $fileName) {
            $this->imageService->sendImageToCloud($file, BlogPost::FOLDER, $fileName);
            $this->getContext()->getEntity()->getInstance()->setLogo($fileName);
        })
        ->onlyOnForms()
        ;
        yield BooleanField::new('deleteLogo', 'Supprimer le fichier actuel')
        ->onlyOnForms();

        yield TrumbowygField::new('hat', 'Texte d’introduction')
        ->onlyOnForms()
        ;
        yield TrumbowygField::new('description', 'Contenu')
        ->onlyOnForms()
        ;

        yield FormField::addFieldset('Administration');
        yield ChoiceField::new('status', 'Statut')
        ->setChoices([
            'Brouillon' => 'draft',
            'En revue' => 'reviewable',
            'Publié' => 'published',
            'Supprimé' => 'deleted'
        ])
        ;

        yield DateField::new('datePublished', 'Date de publication')
        ;

        yield FormField::addFieldset('SEO');
        yield TextField::new('metaTitle', 'Titre (balise meta)')
        ->setHelp('Le titre qui sera affiché dans les SERPs. Il est recommandé de le garder < 60 caractères. Laissez vide pour réutiliser le titre de l’article.')
        ;
        yield TextField::new('metaDescription', 'Description (balise meta)')
        ->setHelp('La description qui sera affiché dans les SERPs. Il est recommandé de le garder < 120 caractères. ')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // action pour afficher le qrCode
        $displayOnFront = Action::new('displayOnFront', 'Afficher sur le site', 'far fa-eye')
            ->setHtmlAttributes(['title' => 'Afficher sur le site', 'target' => '_blank']) // titre
            ;

        //set the link using a string or a callable (function like its being used here)
        $displayOnFront->linkToUrl(function($entity) {
            return $this->generateUrl('app_blog_post_details', ['slug' => $entity->getSlug()]);
        });
        
        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->add(Crud::PAGE_EDIT, $displayOnFront)
        ;
    }
}
