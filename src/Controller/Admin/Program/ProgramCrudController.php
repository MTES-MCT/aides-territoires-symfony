<?php

namespace App\Controller\Admin\Program;

use App\Controller\Admin\AtCrudController;
use App\Entity\Program\Program;
use App\Field\TextLengthCountField;
use App\Field\TrumbowygField;
use App\Field\VichImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProgramCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Program::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')
        ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off']);
        yield BooleanField::new('isSpotlighted', 'Le programme est-il mis en avant')
        ->setHelp('Si le programme est mis en avant, son logo apparaît sur la page d’accueil')
        ->hideOnIndex();
        yield TextLengthCountField::new('shortDescription', 'Description courte')
        ->setHelp('300 caractères max. Résultats de recherche uniquement.')
        ->setFormTypeOption('attr', ['maxlength' => 300])
        ->hideOnIndex();
        yield TrumbowygField::new('description', 'Contenu')
        ->hideOnIndex()
        ->hideOnIndex();

        yield ImageField::new('logoFile', 'Logo')
        ->setHelp('Évitez les fichiers trop lourds. Préférez les fichiers SVG.')
        ->setUploadDir($this->fileService->getUploadTmpDirRelative())
        ->setBasePath($this->paramService->get('cloud_image_url'))
        ->setUploadedFileNamePattern(Program::FOLDER.'/[slug]-[timestamp].[extension]')
        ->setFormTypeOption('upload_new', function(UploadedFile $file, string $uploadDir, string $fileName) {
            $this->imageService->sendImageToCloud($file, Program::FOLDER, $fileName);
            $this->getContext()->getEntity()->getInstance()->setLogo($fileName);
        })
        ->onlyOnForms()
        ;
        yield BooleanField::new('deleteLogo', 'Supprimer le fichier actuel')
        ->onlyWhenUpdating();

        yield AssociationField::new('perimeter', 'Périmètre')
        ->autocomplete();

        yield FormField::addFieldset('SEO');
        yield TextField::new('metaTitle', 'Titre (balise meta)')
        ->setHelp('Le titre qui sera affiché dans les SERPs. Il est recommandé de le garder < 60 caractères. Laissez vide pour réutiliser le nom du programme.')
        ->hideOnIndex();
        yield TextField::new('metaDescription', 'Description (balise meta)')
        ->setHelp('Sera affichée dans les SERPs. À garder < 120 caractères.')
        ->hideOnIndex();

        yield FormField::addFieldset('Données diverses');
        yield IntegerField::new('nbAids', 'Nombre d\'aides')
        ->setFormTypeOption('attr', ['readonly' => true]);
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideWhenCreating();
    }

    public function configureActions(Actions $actions): Actions
    {
        // action pour afficher le qrCode
        $displayOnFront = Action::new('displayOnFront', 'Afficher sur le site', 'far fa-eye')
            ->setHtmlAttributes(['title' => 'Afficher sur le site', 'target' => '_blank']) // titre
            ;

        //set the link using a string or a callable (function like its being used here)
        $displayOnFront->linkToUrl(function($entity) {
            return $this->generateUrl('app_program_details', ['slug' => $entity->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
        });
        
        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->add(Crud::PAGE_EDIT, $displayOnFront)
        ;
    }
}
