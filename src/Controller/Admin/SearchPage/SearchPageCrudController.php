<?php

namespace App\Controller\Admin\SearchPage;

use App\Controller\Admin\AtCrudController;
use App\Controller\Admin\Page\PageLinkSearchPageCrudController;
use App\Entity\Aid\Aid;
use App\Entity\Search\SearchPage;
use App\Field\TextLengthCountField;
use App\Field\TrumbowygField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SearchPageCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchPage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity()->getInstance() ?? null;

        yield FormField::addTab('Informations générales');
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('slug', 'Url')
        ->setHelp('Par exemple, « /a_propos/contact/ ». Vérifiez la présence du caractère « / » en début et en fin de chaîne.');
        yield TextField::new('name', 'Titre')
        ->setHelp('Le titre principal.');
        yield AssociationField::new('administrator', 'Administrateur')
        ->autocomplete()
        ->hideOnIndex();
        yield TextField::new('shortTitle', 'Titre court')
        ->setHelp('Un titre plus concis, pour affichage spécifique')
        ->hideOnIndex();

        yield FormField::addTab('Contenu');
        yield TrumbowygField::new('description', 'Contenu de la page')
        ->setHelp('Description complète de la page. Sera affichée au dessus des résultats.')
        ->hideOnIndex();
        yield TrumbowygField::new('moreContent', 'Contenu additionnel')
        ->setHelp('Contenu caché, révélé au clic sur le bouton « Voir plus »')
        ->hideOnIndex();
        yield FormField::addFieldset('À propos de cette page');
        if ($entity && $entity->getTimeCreate()) {
            yield DateTimeField::new('timeCreate', 'Date de création')
            ->setFormTypeOption('attr', ['readonly' => true])
            ;
        }

        yield FormField::addTab('Configuration');
        yield AssociationField::new('searchPageRedirect', 'Portail vers lequel rediriger');
        yield TextLengthCountField::new('slug', 'Fragment d’URL')
        ->setHelp('Cette partie est utilisée dans l’URL. NE PAS CHANGER pour une page. DOIT être en minuscule pour les sites partenaires. Longueur max :33 caractères, mais si possible ne pas dépasser 23.')
        ->setFormTypeOption('attr', ['maxlength' => 33]);
        yield UrlField::new('contactLink', 'URL du lien contact')
        ->setHelp('URL ou adresse email qui sera utilisé pour le lien « contact » dans le footer.')
        ->hideOnIndex();
        yield ImageField::new('logoFile', 'Logo')
        ->setHelp('Évitez les fichiers trop lourds. Préférez les fichiers SVG.')
        ->setUploadDir($this->fileService->getUploadTmpDirRelative())
        ->setBasePath($this->paramService->get('cloud_image_url'))
        ->setUploadedFileNamePattern(SearchPage::FOLDER.'/[slug]-[timestamp].[extension]')
        ->setFormTypeOption('upload_new', function(UploadedFile $file, string $uploadDir, string $fileName) {
            $this->imageService->sendUploadedImageToCloud($file, SearchPage::FOLDER, $fileName);
            $this->getContext()->getEntity()->getInstance()->setLogo($fileName);
        })
        ->onlyOnForms()
        ;
        yield BooleanField::new('deleteLogo', 'Supprimer le fichier actuel')
        ->onlyWhenUpdating();

        yield UrlField::new('logoLink', 'Lien du logo')
        ->setHelp('L’URL vers laquelle renvoie un clic sur le logo partenaire')
        ->hideOnIndex();

        yield FormField::addTab('Recherche');
        yield TextareaField::new('searchQuerystring', 'Querystring')
        ->setHelp('Les paramètres de recherche en format URL')
        ->hideOnIndex();
        yield BooleanField::new('showAudienceField', 'Montrer le champ « structure »')
        ->hideOnIndex();
        yield AssociationField::new('organizationTypes', 'Bénéficiaires de l’aide')
        ->hideOnIndex();
        yield BooleanField::new('showPerimeterField', 'Montrer le champ « territoire »')
        ->hideOnIndex();
        yield BooleanField::new('showTextField', 'Montrer le champ « recherche textuelle »')
        ->hideOnIndex();
        yield BooleanField::new('showCategoriesField', 'Montrer le champ « thématiques »')
        ->hideOnIndex();
        yield AssociationField::new('categories', 'Sous-thématiques')
        ->setFormTypeOption('choice_label', function($entity) {
            $return = '';
            if ($entity->getCategoryTheme()) {
                $return .= $entity->getCategoryTheme()->getName(). ' > ';
            }
            $return .= $entity->getName();
            return $return;
        })
        ->hideOnIndex();
        yield BooleanField::new('showAidTypeField', 'Montrer le champ « nature de l’aide »')
        ->hideOnIndex();
        yield BooleanField::new('showBackersField', 'Montrer le champ « porteur »')
        ->hideOnIndex();
        yield BooleanField::new('showMobilizationStepField', 'Montrer le champ « avancement du projet »')
        ->hideOnIndex();

        yield FormField::addTab('Aides');
        $aidParams = [];
        if ($entity && $entity->getSearchQuerystring()) {
            $queryString = null;
            $query = parse_url($entity->getSearchQuerystring())['query'] ?? null;
            $queryString = $query ?? $entity->getSearchQuerystring();
            $aidSearchClass = $this->aidSearchFormService->getAidSearchClass(
                params: [
                    'querystring' => $queryString,
                    'forceOrganizationType' => null,
                    'dontUseUserPerimeter' => true
                    ]
            );
            $aidParams = [
                'showInSearch' => true,
            ];
            $aidParams = array_merge($aidParams, $this->aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));
            $aidParams['searchPage'] = $entity;
            $aids = $this->aidService->searchAids($aidParams);
            $nbAids = count($aids);
            $nbLocals = 0;
            /** @var Aid $aid */
            foreach ($aids as $aid) {
                if ($aid->isLocal()) {
                    $nbLocals++;
                }
            }

            $this->getContext()->getEntity()->getInstance()->setNbAids(
                $nbAids
            );
        }

        yield IntegerField::new('nbAids', 'Nombre d\'aides total (querystring)')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideOnIndex();
        if ($entity && $entity->getSearchQuerystring() && isset($nbLocals)) {
            $this->getContext()->getEntity()->getInstance()->setNbAidsLive(
                $nbLocals
            );
        }
        yield IntegerField::new('nbAidsLive', 'Nombre d\'aides actuellement visible')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideOnIndex();

        yield FormField::addFieldset('Mettre en avant des aides');
        yield AssociationField::new('highlightedAids', 'Aides à mettre en avant')
        ->setHelp('Il est possible de mettre jusqu’à 9 aides en avant. Les aides mises en avant s’affichent en haut des résultats du portail, et n’ont pas de mise en forme particulière.')
        ->autocomplete()
        ->hideOnIndex()
        ;

        yield FormField::addFieldset('Exclure des aides des résultats');
        yield AssociationField::new('excludedAids', 'Aides à exclure')
        ->autocomplete()
        ->hideOnIndex()
        ;

        yield FormField::addTab('Onglets');
        yield TextField::new('tabTitle', 'Titre de l’onglet principal')
        ->hideOnIndex();
        yield CollectionField::new('pages', 'Onglets')
        ->useEntryCrudForm(PageLinkSearchPageCrudController::class)
        ->setColumns(12)
        ->hideOnIndex()
        ;

        yield FormField::addTab('Divers');
        yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideOnIndex();

        yield FormField::addTab('SEO');
        yield TextLengthCountField::new('metaTitle', 'Titre (balise meta)')
        ->setHelp('Le titre qui sera affiché dans les SERPs. Il est recommandé de le garder < 60 caractères. Laissez vide pour réutiliser le titre de la page.')
        ->setFormTypeOption('attr', ['maxlength' => 180])
        ->hideOnIndex();
        yield TextLengthCountField::new('metaDescription', 'Description (balise meta)')
        ->setHelp('Sera affichée dans les SERPs. À garder < 120 caractères.')
        ->setFormTypeOption('attr', ['maxlength' => 255]);

        yield ImageField::new('metaImageFile', 'Image (balise meta)')
        ->setHelp('Vérifiez que l’image a une largeur minimale de 1024px')
        ->setUploadDir($this->fileService->getUploadTmpDirRelative())
        ->setBasePath($this->paramService->get('cloud_image_url'))
        ->setUploadedFileNamePattern(SearchPage::FOLDER.'/[slug]-[timestamp].[extension]')
        ->setFormTypeOption('upload_new', function(UploadedFile $file, string $uploadDir, string $fileName) {
            $this->imageService->sendUploadedImageToCloud($file, SearchPage::FOLDER, $fileName);
            $this->getContext()->getEntity()->getInstance()->setMetaImage($fileName);
        })
        ->onlyOnForms()
        ;
        yield BooleanField::new('deleteMetaImage', 'Supprimer le fichier actuel')
        ->onlyWhenUpdating();



        yield FormField::addTab('Obsolète');
        yield BooleanField::new('subdomainEnabled', 'Afficher depuis un sous-domaine ?')
        ->hideOnIndex();
        yield ColorField::new('color1', 'Couleur 1')
        ->setHelp('Couleur du fond principal')
        ->hideOnIndex();
        yield ColorField::new('color2', 'Couleur 2')
        ->setHelp('Couleur du formulaire de recherche')
        ->hideOnIndex();
        yield ColorField::new('color3', 'Couleur 3')
        ->setHelp('Couleur des boutons et bordures de titres')
        ->hideOnIndex();
        yield ColorField::new('color4', 'Couleur 4')
        ->setHelp('Couleur des liens')
        ->hideOnIndex();
        yield ColorField::new('color5', 'Couleur 5')
        ->setHelp('Couleur de fond du pied de page')
        ->hideOnIndex();
    }

    public function configureActions(Actions $actions): Actions
    {
        // action pour afficher le qrCode
        $displayOnFront = Action::new('displayOnFront', 'Afficher sur le site', 'far fa-eye')
            ->setHtmlAttributes(['title' => 'Afficher sur le site', 'target' => '_blank']) // titre
            ;

        //set the link using a string or a callable (function like its being used here)
        $displayOnFront->linkToUrl(function($entity) {
            return $this->generateUrl('app_portal_portal_details', ['slug' => $entity->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
        });
        
        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->add(Crud::PAGE_EDIT, $displayOnFront)
        ;
    }
}
