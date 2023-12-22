<?php

namespace App\Controller\Admin\Blog;

use App\Controller\Admin\AtCrudController;
use App\Entity\Blog\BlogPromotionPost;
use App\Field\TrumbowygField;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BlogPromotionPostCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPromotionPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Article');
        yield IdField::new('id')
        ->onlyOnIndex();
        yield TextField::new('name', 'Titre de l\'article');
        yield TextField::new('slug', 'Fragment d’URL')
        ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
        ->setHelp('Laisser vide pour autoremplir.')
        ->onlyOnForms()
        ;
        yield TrumbowygField::new('shortText', 'Contenu')
        ->onlyOnForms()
        ;
        yield TextField::new('buttonTitle', 'Titre du bouton')
        ->onlyOnForms();
        yield UrlField::new('buttonLink', 'Lien du bouton')
        ->onlyOnForms();
        yield BooleanField::new('externalLink', 'Lien externe')
        ->onlyOnForms();
        yield ImageField::new('image', 'Illustration')
        ->setHelp('Évitez les fichiers trop lourds.')
        ->setUploadDir($this->fileService->getUploadTmpDirRelative())
        ->setBasePath($this->paramService->get('cloud_image_url'))
        ->setUploadedFileNamePattern(BlogPromotionPost::FOLDER.'/[slug]-[timestamp].[extension]')
        ->setFormTypeOption('upload_new', function(UploadedFile $file, string $uploadDir, string $fileName) {
            $this->imageService->sendImageToCloud($file, BlogPromotionPost::FOLDER, $fileName);
        })
        ->onlyOnForms()
        ->setFormTypeOption('allow_delete', true)
        ;
        yield TextField::new('imageAltText', 'Texte alternatif pour l’image')
        ->onlyOnForms();

        yield FormField::addFieldset('Filtres conditionnant l\'affichage');



        yield AssociationField::new('organizationTypes', 'Bénéficiaires')
        ->setFormTypeOption('choice_label', 'name')
        ->setFormTypeOption(
            'query_builder',
            function (EntityRepository $er) {
                return $er->createQueryBuilder('o')
                ->orderBy('o.name', 'ASC');
            }
        )
        ->hideOnIndex()
        ;
        yield AssociationField::new('backers', 'Porteurs d’aides')
        ->setFormTypeOption('choice_label', 'name')
        ->hideOnIndex()
        ;
        yield AssociationField::new('programs', 'Programmes')
        ->setFormTypeOption('choice_label', 'name')
        ->hideOnIndex()
        ;
        yield AssociationField::new('perimeter', 'Périmètre')
        ->hideOnIndex()
        ->autocomplete(true)
        ;
        yield AssociationField::new('categories', 'Sous-thématiques')
        ->setFormTypeOption('choice_label', 'name')
        ->hideOnIndex()
        ;

        yield FormField::addFieldset('Administration');
        $statusChoices = [];
        foreach (BlogPromotionPost::STATUSES as $status) {
            $statusChoices[$status['name']] = $status['slug'];
        }
        yield ChoiceField::new('status', 'Statut')
        ->setChoices($statusChoices);

        yield FormField::addFieldset('Données diverses');
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideWhenCreating()
        ;
        yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideWhenCreating()
        ->hideOnIndex()
        ;
    }    
}
