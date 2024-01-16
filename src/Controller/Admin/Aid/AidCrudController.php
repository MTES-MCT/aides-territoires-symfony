<?php

namespace App\Controller\Admin\Aid;

use App\Controller\Admin\AtCrudController;
use App\Controller\Admin\Filter\Aid\AidAuthorFilter;
use App\Controller\Admin\Filter\Aid\AidBackerFilter;
use App\Controller\Admin\Filter\Aid\AidPerimeterFilter;
use App\Controller\Admin\Filter\Aid\AidGenericFilter;
use App\Controller\Admin\Filter\Aid\AidStateFilter;
use App\Entity\Aid\Aid;
use App\Field\JsonField;
use App\Field\TextLengthCountField;
use App\Field\TrumbowygField;
use App\Service\Export\CsvExporterService;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AidCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Aid::class;
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Brouillon' => Aid::STATUS_DRAFT,
                'En revue' => Aid::STATUS_REVIEWABLE,
                'Publiée' => Aid::STATUS_PUBLISHED,
                'Supprimée' => Aid::STATUS_DELETED,
                'Fustionnée' => Aid::STATUS_MERGED,
            ]))
            ->add('importUpdated')
            ->add('contactInfoUpdated')
            ->add('hasBrokenLink')
            ->add('datePublished')
            ->add(AidStateFilter::new('state', 'Etat'))
            ->add('isCharged')
            ->add(AidGenericFilter::new('generic', 'Aide générique'))
            ->add('aidRecurrence')
            ->add('isImported')
            ->add('importDataSource')
            ->add('isCallForProject')
            ->add('inFranceRelance')
            ->add(AidAuthorFilter::new('author', 'Auteur'))
            ->add(AidBackerFilter::new('backer', 'Porteur d\'aide'))
            ->add(AidPerimeterFilter::new('perimeter', 'Périmètre'))
            ->add('aidAudiences')
            ->add('programs')
            ->add('categories')
            // most of the times there is no need to define the
            // filter type because EasyAdmin can guess it automatically
            // ->add(BooleanFilter::new('published'))
        ;
    }


    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        $aid = $this->getContext()->getEntity()->getInstance() ?? null;

        if ($aid && is_array($aid->getImportRawObject()) && $aid->isImportUpdated()) {
            $pendingUpdates = [];
            $nbUpdates = 0;
            foreach ($aid->getImportRawObject() as $key => $value) {
                if (isset($aid->getImportRawObjectTemp()[$key])) {
                    $pendingUpdates[] = [
                        'key' => $key,
                        'value' => $value,
                        'newValue' => $aid->getImportRawObjectTemp()[$key] ?? null,
                        'updated' => $aid->getImportRawObjectTemp()[$key] != $value  ? true : false
                    ];
                    if ($aid->getImportRawObjectTemp()[$key] != $value) {
                        $nbUpdates++;
                    }
                }
            }

            if ($nbUpdates > 0) {
                $responseParameters->setIfNotSet('pendingUpdates', $pendingUpdates);
            }
        }
        return $responseParameters;
    }

    public function configureActions(Actions $actions): Actions
    {
        // action pour afficher le qrCode
        $displayOnFront = Action::new('displayOnFront', 'Afficher sur le site', 'far fa-eye')
            ->setHtmlAttributes(['title' => 'Afficher sur le site', 'target' => '_blank']) // titre
            ;

        //set the link using a string or a callable (function like its being used here)
        $displayOnFront->linkToUrl(function($entity) {
            return $this->generateUrl('app_aid_aid_details', ['id' => $entity->getId(), 'slug' => $entity->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
        });

        $exportAction = Action::new('export')
        ->linkToUrl(function () {
            $request = $this->requestStack->getCurrentRequest();
            return $this->adminUrlGenerator->setAll($request->query->all())
                ->setAction('export')
                ->generateUrl();
        })
        ->addCssClass('btn btn-success')
        ->setIcon('fa fa-download')
        ->createAsGlobalAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->add(Crud::PAGE_EDIT, $displayOnFront)
            ->add(Crud::PAGE_INDEX, $exportAction)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity()->getInstance() ?? null;

        yield IdField::new('id')->onlyOnIndex();
        yield BooleanField::new('isLive', 'Live')
        ->onlyOnIndex();
        yield TextLengthCountField::new('name', 'Nom')
        ->setHelp('Le titre doit commencer par un verbe à l’infinitif pour que l’objectif de l’aide soit explicite vis-à-vis de ses bénéficiaires.')
        ->setFormTypeOption('attr', ['maxlength' => 180])
        ->setColumns(12);
        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Laisser vide pour autoremplir.')
            ->hideOnIndex()
            ->setColumns(12)
        ;
        yield BooleanField::new('inFranceRelance', 'France Relance')
        ->setHelp('Cette aide est-elle éligible au programme France Relance ?')
        ->hideOnIndex()
        ->setColumns(12);
        $europeanAidChoices = [];
        foreach (Aid::LABELS_EUROPEAN as $slug => $name) {
            $europeanAidChoices[$name] = $slug;
        }
        yield ChoiceField::new('europeanAid', 'Aide européenne ?')
        ->setChoices($europeanAidChoices)
        ->setHelp('* Les fonds structurels (FEDER, FSE+...) sont les aides gérées par les conseils régionaux.')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('nameInitial', 'Nom initial')
        ->setHelp('Comment cette aide s’intitule-t-elle au sein de votre structure ? Exemple : AAP Mob’Biodiv')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('shortTitle', 'Titre du programme')
        ->setFormTypeOption('attr', ['placeholder' => 'Ex: Appel à projet innovation continue'])
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('categories', 'Thématiques')
        ->setFormTypeOption('choice_label', function($entity) {
            $return = '';
            if ($entity->getCategoryTheme()) {
                $return .= $entity->getCategoryTheme()->getName().' > ';
            }
            $return .= $entity->getName();
            return $return;
        })
        ->setFormTypeOptions([
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                ->innerJoin('c.categoryTheme', 'categoryTheme')
                ->orderBy('categoryTheme.name', 'ASC');
            },
        ])
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('aidAudiences', 'Bénéficiaires de l’aide')
        ->setFormTypeOption('choice_label', function($entity) {
            $return = '';
            if ($entity->getOrganizationTypeGroup()) {
                $return .= $entity->getOrganizationTypeGroup()->getName().' > ';
            }
            $return .= $entity->getName();
            return $return;
        })
        ->setFormTypeOptions([
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('ot')
                ->innerJoin('ot.organizationTypeGroup', 'organizationTypeGroup')
                ->orderBy('organizationTypeGroup.name', 'ASC');
            },
        ])
        ->hideOnIndex()
        ->setColumns(12)
        ;
        yield AssociationField::new('author', 'Auteur')
        ->autocomplete();
        $nbAidsLive = 0;
        if ($entity && $entity->getAuthor()) {
            $nbAidsLive = $entity->getAuthor()->getNbAidsLive();
        }
        yield IntegerField::new('nbAidsLive', 'Du même auteur')
        ->setHelp('Nb. d\'aides live créées par le même utilisateur')
        ->setFormTypeOptions([
            'data' => $nbAidsLive,
            'attr' => ['readonly' => true],
            'mapped' => false
        ])
        ->setColumns(12);

        yield FormField::addFieldset('Porteurs d’aides');
        yield CollectionField::new('aidFinancers', 'Porteurs d\'aides')
        ->useEntryCrudForm(AidFinancerAddBackerToAidCrudController::class)
        ->setColumns(12)
        ->formatValue(function ($value) {
            return implode('<br>', $value->toArray());
        })
        ;

        yield FormField::addFieldset('Porteurs d\'aides suggérés');
        yield TextField::new('financerSuggestion', 'Porteurs suggérés')
        ->setHelp('Ce porteur a été suggéré. Créez le nouveau porteur et ajouter le en tant que porteur d’aides via le champ approprié.')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Instructeurs');
        yield CollectionField::new('aidInstructors', 'Instructeurs')
        ->useEntryCrudForm(AidInstructorAddBackerToAidCrudController::class)
        ->setColumns(12)
        ->formatValue(function ($value) {
            return implode('<br>', $value->toArray());
        })
        ;
        
        yield FormField::addFieldset('Instructeurs suggérés');
        yield TextField::new('instructorSuggestion', 'Instructeurs suggérés')
        ->setHelp('Cet instructeur a été suggéré. Créez le nouveau porteur et ajouter le en tant qu’instructeur via le champ approprié.')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Périmètre de l’aide');
        yield AssociationField::new('perimeter', 'Périmètre')
        ->autocomplete()
        ->setColumns(12);
        yield TextField::new('perimeterSuggestion', 'Périmètre suggéré')
        ->setHelp('Le contributeur suggère ce nouveau périmètre')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Calendrier de l’aide');
        yield AssociationField::new('aidRecurrence', 'Récurrence')
        ->setHelp('L’aide est-elle ponctuelle, permanente, ou récurrente ?')
        ->hideOnIndex()
        ->setColumns(12);
        yield DateField::new('dateStart', 'Date d’ouverture')
        ->setHelp('À quelle date l’aide est-elle ouverte aux candidatures ?')
        ->hideOnIndex()
        ->setColumns(12);
        yield DateField::new('datePredeposit', 'Date de pré-dépôt')
        ->setHelp('Quelle est la date de pré-dépôt des dossiers, si applicable ?')
        ->hideOnIndex()
        ->setColumns(12);
        yield DateField::new('dateSubmissionDeadline', 'Date de clôture')
        ->setHelp('Quelle est la date de clôture de dépôt des dossiers ?')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Description de l’aide');
        yield BooleanField::new('isCallForProject', 'Appel à projet / Manifestation d’intérêt')
        ->hideOnIndex()
        ->setColumns(12);
        yield BooleanField::new('isCharged', 'Aide Payante')
        ->setHelp('Ne pas cocher pour les aides sous adhésion et ajouter la mention « *sous adhésion » dans les critères d’éligibilité.')
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('programs', 'Programmes')
        ->autocomplete()
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('aidTypes', 'Types d\'aide')
        ->setFormTypeOption('choice_label', function ($entity) {
            $return = '';
            if ($entity->getAidTypeGroup()) {
                $return .= $entity->getAidTypeGroup()->getName(). ' > ';
            }
            $return .= $entity->getName();

            return $return;
        })
        ->setFormTypeOptions([
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('at')
                ->innerJoin('at.aidTypeGroup', 'aidTypeGroup')
                ->orderBy('aidTypeGroup.name', 'ASC');
            },
        ])
        ->setHelp('Précisez le ou les types de l’aide.')
        ->hideOnIndex()
        ->setColumns(12);
        yield IntegerField::new('subventionRateMin', 'Taux de subvention min. (en %, nombre entier)')
        ->hideOnIndex()
        ->setColumns(12);
        yield IntegerField::new('subventionRateMax', 'Taux de subvention max. (en %, nombre entier)')
        ->setHelp('Si le taux est fixe, remplissez uniquement le taux max.')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('subventionComment', 'Taux de subvention (commentaire optionnel)')
        ->hideOnIndex()
        ->setColumns(12);
        yield IntegerField::new('loanAmount', 'Montant du prêt maximum')
        ->hideOnIndex()
        ->setColumns(12);
        yield IntegerField::new('recoverableAdvanceAmount', 'Montant de l’avance récupérable')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('otherFinancialAidComment', 'Autre aide financière (commentaire optionnel)')
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('aidSteps', 'État d’avancement du projet pour bénéficier du dispositif ')
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('aidDestinations', 'Types de dépenses / actions couvertes')
        ->setHelp('Obligatoire pour les aides financières')
        ->hideOnIndex()
        ->setColumns(12);
        yield TrumbowygField::new('description', 'Description complète de l’aide et de ses objectifs')
        ->hideOnIndex()
        ->setColumns(12);
        yield TrumbowygField::new('projectExamples', 'Exemples d’applications ou de projets réalisés grâce à cette aide')
        ->setHelp('Afin d’aider les territoires à mieux comprendre votre aide, donnez ici quelques exemples concrets de projets réalisables ou réalisés.')
        ->hideOnIndex()
        ->setColumns(12);
        yield TrumbowygField::new('eligibility', 'Conditions d’éligibilité')
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('keywords', 'Mots clés')
        ->setFormTypeOptions([
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('k')
                ->orderBy('k.name', 'ASC');
            },
        ])
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Contact et démarches');
        yield UrlField::new('originUrl', 'Plus d’informations')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('originUrlText', 'Texte du bouton plus d’informations')
        ->setHelp('Texte du bouton plus d’informations. Laisser vide pour utiliser le texte par défaut.')
        ->onlyOnForms()
        ->setColumns(12);

        yield UrlField::new('applicationUrl', 'Candidater à l’aide')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('applicationUrlText', 'Texte du bouton de candidature')
        ->setHelp('Texte du bouton de candidature. Laisser vide pour utiliser le texte par défaut.')
        ->onlyOnForms()
        ->setColumns(12);

        yield BooleanField::new('hasBrokenLink', 'Contient un lien cassé ?')
        ->hideOnIndex()
        ->setColumns(12);
        yield TrumbowygField::new('contact', 'Contact pour candidater')
        ->setHelp('N’hésitez pas à ajouter plusieurs contacts')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('contactPhone', 'Numéro de téléphone')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('contactEmail', 'Adresse e-mail de contact')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('contactDetail', 'Contact (détail)')
        ->hideOnIndex()
        ->setColumns(12);
        yield BooleanField::new('contactInfoUpdated', 'En attente de revue des données de contact mises à jour')
        ->setHelp('Cette aide est en attente d’une revue des données de contact')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Uniquement pour les aides sur Démarches-Simplifiées');
        yield BooleanField::new('dsSchemaExists', 'Schéma existant')
        ->setHelp('Un schéma pour l’api de pré-remplissagede Démarches-Simplifiées est-il renseigné ?')
        ->hideOnIndex()
        ->setColumns(12);
        yield IntegerField::new('dsId', 'Identifiant de la démarche')
        ->setHelp('Identifiant de la démarche sur Démarches-Simplifiées')
        ->hideOnIndex()
        ->setColumns(12);

        yield JsonField::new('dsMapping', 'Mapping JSON de la démarche')
        ->setHelp('Mapping JSON pour pré-remplissage sur Démarches-Simplifiées')
        ->hideOnIndex()
        ->setColumns(12)
        ;

        yield FormField::addFieldset('Éligibilité');
        yield AssociationField::new('eligibilityTest', 'Test d’éligibilité')
        ->autocomplete()
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Administration de l’aide');
        $statusChoices = [];
        foreach (Aid::STATUSES as $status) {
            $statusChoices[$status['name']] = $status['slug'];
        }
        yield ChoiceField::new('status', 'Statut')
        ->setChoices($statusChoices)
        ->hideOnIndex()
        ->setColumns(12);
        yield BooleanField::new('importUpdated', 'En attente de revue des données importées mises à jour')
        ->setHelp('Cette aide est en attente d’une revue des mises à jour proposées par l’outil d’import')
        ->hideOnIndex()
        ->setColumns(12);
        yield BooleanField::new('importUpdated', 'Revue à faire')
        ->setHelp('Cette aide est en attente d’une revue des mises à jour proposées par l’outil d’import')
        ->onlyOnIndex()
        ->setColumns(12);

        yield BooleanField::new('authorNotification', 'Envoyer un email à l’auteur de l’aide ?')
        ->setHelp('Un email doit-il être envoyé à l’auteur de cette aide au moment de sa publication ?')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Uniquement pour les aides génériques');
        yield BooleanField::new('isGeneric', 'Aide générique')
        ->setHelp('Cette aide est-elle générique ?')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Uniquement pour les aides locales');
        yield AssociationField::new('genericAid', 'Aide générique')
        ->setHelp('Aide générique associée à une aide locale.')
        ->setFormTypeOptions([
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('a')
                ->andWhere('a.isGeneric = :isGeneric')
                ->setParameter('isGeneric', true)
                ->orderBy('a.name', 'ASC')
                ;
            },
        ])
        ->hideOnIndex()
        ->setColumns(12);
        yield TrumbowygField::new('localCharacteristics', 'Spécificités locales')
        ->setHelp('Décrivez les spécificités de cette aide locale.')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Données liées à l’import');
        yield BooleanField::new('isImported', 'Importé')
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('importDataSource', 'Source de données')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('importUniqueid', 'Identifiant d’import unique ')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('importDataMention', 'Mention du partenariat avec le propriétaire de la donnée')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('importDataUrl', 'URL d’origine de la donnée importée')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('importShareLicence', 'Sous quelle licence cette aide a-t-elle été partagée ?')
        ->hideOnIndex()
        ->setColumns(12);
        yield DateTimeField::new('dateImportLastAccess', 'Date du dernier accès')
        ->hideOnIndex()
        ->setColumns(12);
        // yield ArrayField::new('importRawObject', 'Donnée brute importée')
        // ->hideOnIndex();
        // yield ArrayField::new('importRawObjectTemp', 'Donnée brute importée temporaire')
        // ->hideOnIndex();
        // yield ArrayField::new('importRawObjectCalendar', 'Donnée brute importée pour le calendrier')
        // ->hideOnIndex();
        // yield ArrayField::new('importRawObjectTempCalendar', 'Donnée brute importée temporaire pour le calendrie')
        // ->hideOnIndex();

        yield FormField::addFieldset('Données diverses');
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideOnIndex()
        ->setColumns(12)
        ->hideWhenCreating();
        yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideOnIndex()
        ->hideWhenCreating()
        ->setColumns(12);
        yield DateTimeField::new('timePublished', 'Première date et heure de publication')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->setColumns(12)
        ->hideOnIndex();
        yield DateTimeField::new('datePublished', 'Première date de publication')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->setColumns(12);
        yield TextField::new('status', 'Statut')
        ->onlyOnIndex()
        ->setColumns(12);
    }


    public function  configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
        ->overrideTemplate('crud/edit', 'admin/aid/edit.html.twig')  
        ;
    }

    public function export(AdminContext $context, CsvExporterService $csvExporterService)
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1.5G');

        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);

        return $csvExporterService->createResponseFromQueryBuilder(
            $queryBuilder,
            $fields,
            $context->getEntity()->getFqcn(),
            'aides.csv'
        );
    }
}
