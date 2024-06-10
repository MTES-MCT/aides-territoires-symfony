<?php

namespace App\Controller\Admin\Aid;

use App\Controller\Admin\AtCrudController;
use App\Controller\Admin\Backer\BackerCrudController;
use App\Controller\Admin\Filter\Aid\AidAuthorFilter;
use App\Controller\Admin\Filter\Aid\AidBackerFilter;
use App\Controller\Admin\Filter\Aid\AidPerimeterFilter;
use App\Controller\Admin\Filter\Aid\AidGenericFilter;
use App\Controller\Admin\Filter\Aid\AidInstructorFilter;
use App\Controller\Admin\Filter\Aid\AidKeywordReferenceSearchFilter;
use App\Controller\Admin\Filter\Aid\AidNoReferenceFilter;
use App\Controller\Admin\Filter\Aid\AidReferenceSearchFilter;
use App\Controller\Admin\Filter\Aid\AidReferenceSearchObjectFilter;
use App\Controller\Admin\Filter\Aid\AidStateFilter;
use App\Entity\Aid\Aid;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Reference\ProjectReference;
use App\Field\AddNewField;
use App\Field\JsonField;
use App\Field\TextLengthCountField;
use App\Field\TrumbowygField;
use App\Form\Admin\Aid\KeywordReferenceAssociationType;
use App\Form\Admin\Aid\ProjectReferenceAssociationType;
use App\Service\Export\SpreadsheetExporterService;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AidCrudController extends AtCrudController
{
    private $symbolWarning = '&#9888;';
    public static function getEntityFqcn(): string
    {
        return Aid::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        $assets = parent::configureAssets($assets);
        return $assets
            ->addWebpackEncoreEntry('import-scss/admin/aid/associate')
            ->addWebpackEncoreEntry('form/entity-checkbox-absolute-type')
            ->addWebpackEncoreEntry('admin/aid/associate')
        ;
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
            ->add(AidInstructorFilter::new('instructor', 'Instructeur'))
            ->add(AidPerimeterFilter::new('perimeter', 'Périmètre'))
            ->add('aidAudiences')
            ->add('programs')
            ->add('categories')
            ->add('aidTypes')
            ->add('projectReferences')
            ->add(AidKeywordReferenceSearchFilter::new('keyReferenceSearch', 'Recherche de mot-clé référence'))
            ->add(AidReferenceSearchFilter::new('referenceSearch', 'Recherche de référence'))
            ->add(AidReferenceSearchObjectFilter::new('referenceSearchObject', 'Recherche de référence (objets uniquement)'))
            ->add(AidNoReferenceFilter::new('noReference', 'Pas de projet référent associé'))
        ;
    }


    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        // formulaire association projet referent en batch
        $formProjectReferenceAssociation = $this->createForm(ProjectReferenceAssociationType::class);
        $responseParameters->setIfNotSet('formProjectReferenceAssociation', $formProjectReferenceAssociation);
        
        // formulaire association mots clés referent en batch
        $formKeywordReferenceAssociation = $this->createForm(KeywordReferenceAssociationType::class);
        $responseParameters->setIfNotSet('formKeywordReferenceAssociation', $formKeywordReferenceAssociation);

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
            return $this->generateUrl('app_aid_aid_details', ['slug' => $entity->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
        });

        // lien affichage stats
        $displayStats = Action::new('displayStats', 'Statistiques', 'fas fa-chart-line')
            ->setHtmlAttributes(['title' => 'Statistiques', 'target' => '_blank']) // titre
            ->linkToUrl(function($entity) {
                return $this->generateUrl('app_user_aid_stats', ['slug' => $entity->getSlug()]);
            });
        ;

        // exports
        $exportCsvAction = $this->getExportCsvAction();
        $exportXlsxAction = $this->getExportXlsxAction();


        $batchAssociate = Action::new('batchAssociate', 'Associé')
        ->linkToCrudAction('batchAssociate');

        $batchAssociateKeyword = Action::new('batchAssociateKeyword', 'Associé Mot clés')
        ->linkToCrudAction('batchAssociateKeyword');

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->add(Crud::PAGE_INDEX, $displayStats)
            ->add(Crud::PAGE_EDIT, $displayOnFront)
            ->add(Crud::PAGE_INDEX, $exportCsvAction)
            ->add(Crud::PAGE_INDEX, $exportXlsxAction)
            ->addBatchAction($batchAssociate)
            ->addBatchAction($batchAssociateKeyword)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity()->getInstance() ?? null;
        $badgeNoBackerAssociate = '<span class="badge badge-warning">(pas de porteur associé)</span>';
        $badgeNoBackerValid = '<span class="badge badge-warning">(porteur associé non validé)</span>';
        //-------------------------------------------------------
        yield FormField::addTab('Informations générales');

        yield FormField::addFieldset('Statut');
        $statusChoices = [];
        foreach (Aid::STATUSES as $status) {
            $statusChoices[$status['name']] = $status['slug'];
        }
        yield ChoiceField::new('status', 'Statut')
        ->setChoices($statusChoices)
        ->hideOnIndex()
        ->setColumns(12)
        ;
        yield TextField::new('status', 'Statut')
        ->onlyOnIndex()
        ->setColumns(12);
        yield BooleanField::new('authorNotification', 'Envoyer un email à l’auteur de l’aide ?')
        ->setHelp('Un email doit-il être envoyé à l’auteur de cette aide au moment de sa publication ?')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Noms');
        yield IdField::new('id')->onlyOnIndex();
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
        yield TextField::new('nameInitial', 'Nom initial')
        ->setHelp('Comment cette aide s’intitule-t-elle au sein de votre structure ? Exemple : AAP Mob’Biodiv')
        ->hideOnIndex()
        ->setColumns(12);

        yield FormField::addFieldset('Associations');

        yield AssociationField::new('categories', 'Thématiques')
        ->setFormTypeOption('choice_label', function($entity) {
            $return = '';
            if ($entity) {
                if ($entity->getCategoryTheme()) {
                    $return .= $entity->getCategoryTheme()->getName().' > ';
                }
                $return .= $entity->getName();
            }
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

        yield AssociationField::new('projectReferences', 'Projets référents')
        ->hideOnIndex();
        yield ArrayField::new('projectReferences', 'Projets référents')
        ->formatValue(function ($value, $entity) {
        return implode('', array_map(function ($projectReference) {
            return '- '.$projectReference->getName().'<br>';
        }, $value->toArray()));
        })
        ->onlyOnIndex();
        yield ArrayField::new('keywordReferences', 'Mots clés référents')
        ->formatValue(function ($value, $entity) {
        return implode('', array_map(function ($keywordReference) {
            return '- '.$keywordReference->getName().'<br>';
        }, $value->toArray()));
        })
        ->onlyOnIndex();
        yield AssociationField::new('keywordReferences', 'Mots clés références')
        ->setFormTypeOptions([
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('k')
                ->orderBy('k.name', 'ASC');
            },
            'choice_label' => function ($entity) {
                $label = $entity->getName();
                if ($entity->getParent()) {
                    $label .= ' ('.$entity->getParent()->getName().')';
                }
                return $label;
            }
        ])
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('aidAudiences', 'Bénéficiaires de l’aide')
        ->setFormTypeOption('choice_label', function($entity) {
            $return = '';
            if ($entity) {
                if ($entity->getOrganizationTypeGroup()) {
                    $return .= $entity->getOrganizationTypeGroup()->getName().' > ';
                }
                $return .= $entity->getName();
            }
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
        yield AssociationField::new('programs', 'Programmes')
        ->autocomplete()
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('aidTypes', 'Types d\'aide')
        ->setFormTypeOption('choice_label', function ($entity) {
            $return = '';
            if ($entity) {
                if ($entity->getAidTypeGroup()) {
                    $return .= $entity->getAidTypeGroup()->getName(). ' > ';
                }
                $return .= $entity->getName();
            }
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

                
        yield AssociationField::new('aidSteps', 'État d’avancement du projet pour bénéficier du dispositif ')
        ->hideOnIndex()
        ->setColumns(12);
        yield AssociationField::new('aidDestinations', 'Types de dépenses / actions couvertes')
        ->setHelp('Obligatoire pour les aides financières')
        ->hideOnIndex()
        ->setColumns(12);


        yield FormField::addFieldset('Périmètre de l’aide');
        yield AssociationField::new('perimeter', 'Périmètre')
        ->autocomplete()
        ->setColumns(12)
        ->formatValue(function ($value) {
            /** @var Perimeter $value */
            if ($value) {
                $name = $value->getName();
                if ($value->getScale() == Perimeter::SCALE_COUNTY) {
                    $name .= ' (Département)';
                } else if ($value->getScale() == Perimeter::SCALE_REGION) {
                    $name .= ' (Région)';
                } else if ($value->getScale() == Perimeter::SCALE_COMMUNE) {
                    $name .= ' (Commune)';
                } else if ($value->getScale() == Perimeter::SCALE_ADHOC) {
                    $name .= ' (Adhoc)';
                }
                $display = strlen($name) < 20 ? $name : substr($name, 0, 20).'...';
                return sprintf('<span title="%s">%s</span>', $name, $display);
            } else {
                return '';
            }
        })
        ;

        yield TextField::new('perimeterSuggestion', 'Périmètre suggéré')
        ->setHelp('Le contributeur suggère ce nouveau périmètre')
        ->hideOnIndex()
        ->setColumns(12);

        //-------------------------------------------------------
        yield FormField::addTab('Contacts');
        
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


        yield TrumbowygField::new('contact', 'Contact pour candidater')
        ->setHelp('N’hésitez pas à ajouter plusieurs contacts')
        ->hideOnIndex()
        ->setColumns(12);
        
        //-------------------------------------------------------
        yield FormField::addTab('Descriptions');


        yield FormField::addFieldset('Description de l’aide');


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


        yield TextField::new('contactPhone', 'Numéro de téléphone')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('contactEmail', 'Adresse e-mail de contact')
        ->hideOnIndex()
        ->setColumns(12);
        yield TextField::new('contactDetail', 'Contact (détail)')
        ->hideOnIndex()
        ->setColumns(12);
        
        //-------------------------------------------------------
        
        $hasNoBacker = ($entity && $entity->getOrganization() && !$entity->getOrganization()->getBacker()) ? true : false;
        $hasBackerNotValide = ($entity && $entity->getOrganization() && $entity->getOrganization()->getBacker() && !$entity->getOrganization()->getBacker()->isActive()) ? true : false;
        $symbol = ($hasNoBacker || $hasBackerNotValide) ? ' '.$this->symbolWarning : '';
        yield FormField::addTab('Auteur'.$symbol);

        $currentAction = $this->getContext()->getCrud()->getCurrentAction();
        $label = 'Structure';

        if ($currentAction == Crud::PAGE_EDIT && $hasNoBacker) {
            $label .= ' '.$badgeNoBackerAssociate;
        } elseif ($currentAction == Crud::PAGE_EDIT && $hasBackerNotValide) {
            $label .= ' '.$badgeNoBackerValid;
        }
        yield AssociationField::new('organization', $label)
        ->autocomplete()
        ->formatValue(function ($value) use ($badgeNoBackerAssociate, $badgeNoBackerValid) {
            $return = $value ? $value->getName() : '';
            if ($value && !$value->getBacker()) {
                $return .= ' '.$badgeNoBackerAssociate;
            } elseif ($value && $value->getBacker() && !$value->getBacker()->isActive()) {
                $return .= ' '.$badgeNoBackerValid;
            }
            return $return;
        })
        ->setFormTypeOptions([
            'label_html' => true
        ])
        ;
        
        yield AssociationField::new('author', 'Auteur')
        ->autocomplete()
        ->hideOnIndex();
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
        ->setColumns(12)
        ->hideOnIndex();

        //-------------------------------------------------------
        yield FormField::addTab('Calendrier');

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

        //-------------------------------------------------------
        $symbol = ($entity && $entity->getAidFinancers()->isEmpty()) ? ' '.$this->symbolWarning : '';
        yield FormField::addTab('Porteurs'.$symbol);
        yield FormField::addFieldset('Porteurs d’aides');
        yield AddNewField::new('newBacker', 'Nouveau porteur')
        ->setFormTypeOptions([
            'mapped' => false,
            'attr' => [
                'new_url' => $this->adminUrlGenerator
                ->setController(BackerCrudController::class)
                ->setAction(Action::NEW)
                ->setEntityId(null)
                ->generateUrl(),
                'new_text' => 'Créer un nouveau porteur'
            ]
        ])
        ->hideOnIndex()
        ;
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
        ->hideOnIndex()
        ;
        
        yield FormField::addFieldset('Instructeurs suggérés');
        yield TextField::new('instructorSuggestion', 'Instructeurs suggérés')
        ->setHelp('Cet instructeur a été suggéré. Créez le nouveau porteur et ajouter le en tant qu’instructeur via le champ approprié.')
        ->hideOnIndex()
        ->setColumns(12);

        //-------------------------------------------------------
        yield FormField::addTab('Informations complémentaires');


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

        yield TextField::new('shortTitle', 'Titre du programme')
        ->setFormTypeOption('attr', ['placeholder' => 'Ex: Appel à projet innovation continue'])
        ->hideOnIndex()
        ->setColumns(12);

        yield BooleanField::new('isCharged', 'Aide Payante')
        ->setHelp('Ne pas cocher pour les aides sous adhésion et ajouter la mention « *sous adhésion » dans les critères d’éligibilité.')
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

        yield BooleanField::new('isCallForProject', 'Appel à projet / Manifestation d’intérêt')
        ->hideOnIndex()
        ->setColumns(12);

        yield BooleanField::new('hasBrokenLink', 'Contient un lien cassé ?')
        ->hideOnIndex()
        ->setColumns(12);

        yield BooleanField::new('contactInfoUpdated', 'En attente de revue des données de contact mises à jour')
        ->setHelp('Cette aide est en attente d’une revue des données de contact')
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


        

        ///-------------------------------------------------------
        yield FormField::addTab('Démarches Simplifiées');

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

        ///-------------------------------------------------------
        yield FormField::addTab('Autres');



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
        yield DateTimeField::new('timePublished', '1ère date et heure de publication')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->setColumns(12)
        ->hideOnIndex();
        yield DateTimeField::new('datePublished', '1ère date de publication')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->setColumns(12);


        yield FormField::addFieldset('Éligibilité (obsolète)');
        yield AssociationField::new('eligibilityTest', 'Test d’éligibilité')
        ->autocomplete()
        ->hideOnIndex()
        ->setColumns(12);
        
        ///-------------------------------------------------------
        yield FormField::addTab('Import');
        
        yield FormField::addFieldset('Données liées à l’import');

        yield BooleanField::new('importUpdated', 'En attente de revue des données importées mises à jour')
        ->setHelp('Cette aide est en attente d’une revue des mises à jour proposées par l’outil d’import')
        ->hideOnIndex()
        ->setColumns(12);
        yield BooleanField::new('importUpdated', 'Revue à faire')
        ->setHelp('Cette aide est en attente d’une revue des mises à jour proposées par l’outil d’import')
        ->onlyOnIndex()
        ->setColumns(12);

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
    }


    public function  configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->overrideTemplate('crud/edit', 'admin/aid/edit.html.twig')
            ->overrideTemplate('crud/index', 'admin/aid/index.html.twig')
            ->setPaginatorPageSize(50)
        ;
    }

    public function exportXlsx(AdminContext $context, SpreadsheetExporterService $spreadsheetExporterService, string $filename = 'aides')
    {
        return $this->exportSpreadsheet($context, $spreadsheetExporterService, $filename, 'xlsx');
    }

    public function exportCsv(AdminContext $context, SpreadsheetExporterService $spreadsheetExporterService, string $filename = 'aides')
    {
        return $this->exportSpreadsheet($context, $spreadsheetExporterService, $filename, 'csv');
    }

    public function batchAssociate(BatchActionDto $batchActionDto): RedirectResponse
    {
        $form = $this->createForm(ProjectReferenceAssociationType::class);
        $form->handleRequest($this->getContext()->getRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $projectReference = $form->get('projectReference')->getData();
                if ($projectReference instanceof ProjectReference) {
                    foreach ($batchActionDto->getEntityIds() as $id) {
                        $aid = $this->managerRegistry->getRepository(Aid::class)->find($id);
                        if ($aid instanceof Aid) {
                            $aid->addProjectReference($projectReference);
                            $this->managerRegistry->getManager()->persist($aid);
                        }
                    }
                    $this->managerRegistry->getManager()->flush();
                }
            }
        }

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function batchAssociateKeyword(BatchActionDto $batchActionDto): RedirectResponse
    {
        $form = $this->createForm(KeywordReferenceAssociationType::class);
        $form->handleRequest($this->getContext()->getRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $keywordReferences = $form->get('keywordReferences')->getData();

                foreach ($batchActionDto->getEntityIds() as $id) {
                    $aid = $this->managerRegistry->getRepository(Aid::class)->find($id);
                    if ($aid instanceof Aid) {
                        foreach ($keywordReferences as $keywordReference) {
                            $aid->addKeywordReference($keywordReference);
                        }
                        $this->managerRegistry->getManager()->persist($aid);
                    }
                }
                $this->managerRegistry->getManager()->flush();

            }
        }

        return $this->redirect($batchActionDto->getReferrerUrl());
    }
}
