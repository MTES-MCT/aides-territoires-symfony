<?php

namespace App\Controller\Admin\Page;

use App\Controller\Admin\AtCrudController;
use App\Entity\Page\Page;
use App\Field\TrumbowygField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

class PageCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort(['url' => 'ASC']) // modifie le tri
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // action pour afficher le qrCode
        $displayOnFront = Action::new('displayOnFront', 'Afficher sur le site', 'far fa-eye')
            ->setHtmlAttributes(['title' => 'Afficher sur le site', 'target' => '_blank']) // titre
            ->linkToCrudAction('displayOnFront') // l'action appellée
            // ->displayIf(fn ($entity) => $this->userService->isUserGranted($entity, User::ROLE_ADMIN)); // condition d'affichage
            ;
        
        return $actions
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->add(Crud::PAGE_EDIT, $displayOnFront)
        ;
    }

    public function displayOnFront(AdminContext $context): Response
    {
        $object = $context->getEntity()->getInstance();
        
        return $this->redirect($object->getUrl());
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Page');
        yield IdField::new('id', 'Id')
        ->onlyOnIndex();
        yield TextField::new('url', 'Url')
        ->setHelp('ATTENTION ! NE PAS CHANGER l\'url des pages du menu principal. Par exemple, « /a_propos/contact/ ». Vérifiez la présence du caractère « / » en début et en fin de chaîne.');
        yield TextField::new('name', 'Titre');
        yield TrumbowygField::new('description', 'Contenu')
        ->onlyOnForms();
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->onlyOnIndex();
        yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
        ->onlyOnIndex();
        yield FormField::addTab('Seo');
        yield TextField::new('meta_title', 'Titre (balise meta)')
        ->onlyOnForms()
        ->setHelp('Le titre qui sera affiché dans les SERPs. Il est recommandé de le garder < 60 caractères. Laissez vide pour réutiliser le titre de la page.');
        yield TextField::new('meta_description', 'Description (balise meta)')
        ->onlyOnForms()
        ->setHelp('Le titre qui sera affiché dans les SERPs. Il est recommandé de le garder < 120 caractères.');
    }
}
