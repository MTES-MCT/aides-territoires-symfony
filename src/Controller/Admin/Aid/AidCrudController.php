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
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidInstructor;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\Log\LogAidView;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Reference\ProjectReference;
use App\Entity\Reference\ProjectReferenceMissing;
use App\Entity\Site\UrlRedirect;
use App\Entity\User\User;
use App\Field\AddNewField;
use App\Field\CollectionCopyableField;
use App\Field\JsonField;
use App\Field\TextLengthCountField;
use App\Field\TrumbowygField;
use App\Form\Admin\Aid\AidImportType;
use App\Form\Admin\Aid\KeywordReferenceAssociationType;
use App\Form\Admin\Aid\ProjectReferenceAssociationType;
use App\Form\Admin\Reference\ProjectReferenceMissingCreateType;
use App\Repository\Aid\AidDestinationRepository;
use App\Repository\Aid\AidRecurrenceRepository;
use App\Repository\Aid\AidRepository;
use App\Repository\Aid\AidStepRepository;
use App\Repository\Aid\AidTypeRepository;
use App\Repository\Backer\BackerRepository;
use App\Repository\Category\CategoryRepository;
use App\Repository\Organization\OrganizationRepository;
use App\Repository\Organization\OrganizationTypeRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Program\ProgramRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Repository\User\UserRepository;
use App\Service\Export\SpreadsheetExporterService;
use App\Service\File\FileService;
use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
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
use OpenSpout\Reader\CSV\Options as OptionsCsv;
use OpenSpout\Reader\CSV\Reader as ReaderCsv;
use OpenSpout\Reader\XLSX\Reader as ReaderXlsx;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use TypeError;

class AidCrudController extends AtCrudController implements EventSubscriberInterface
{
    private string $symbolWarning = '&#9888;';
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
            ->add(AidReferenceSearchObjectFilter::new(
                'referenceSearchObject',
                'Recherche de référence (objets uniquement)'
            ))
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

        if ($aid && is_array($aid->getImportDatas()) && $aid->isImportUpdated()) {
            $pendingUpdates = [];
            $nbUpdates = 0;

            foreach ($aid->getImportDatas() as $field => $value) {
                // gestion des booleéns
                $methodGet = 'get';
                if (!method_exists($aid, 'get' . ucfirst($field))) {
                    if (method_exists($aid, 'is' . ucfirst($field))) {
                        $methodGet = 'is';
                    } else {
                        continue;
                    }
                }

                if ($aid->{$methodGet . ucfirst($field)}() != $value) {
                    $percent = 0;
                    if (is_string($value) && is_string($aid->{$methodGet . ucfirst($field)}())) {
                        similar_text($aid->{$methodGet . ucfirst($field)}(), $value, $percent);
                    }
                    $pendingUpdates[] = [
                        'key' => $field,
                        'value' => $aid->{$methodGet . ucfirst($field)}(),
                        'newValue' => $aid->getImportDatas()[$field] ?? null,
                        'updated' => false,
                        'similarPercent' => round($percent, 2)
                    ];
                    $nbUpdates++;
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
        $displayOnFront->linkToUrl(function ($entity) {
            return $this->generateUrl(
                'app_aid_aid_details',
                ['slug' => $entity->getSlug()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        });

        // lien affichage stats
        $displayStats = Action::new('displayStats', 'Statistiques', 'fas fa-chart-line')
            ->setHtmlAttributes(['title' => 'Statistiques', 'target' => '_blank']) // titre
            ->linkToUrl(function ($entity) {
                return $this->generateUrl('app_user_aid_stats', ['slug' => $entity->getSlug()]);
            });

        // import
        $importSpreadsheetAction = Action::new('importSpreadsheet', 'Importer des aides', 'fas fa-file-import')
            ->setHtmlAttributes(['title' => 'Importer des aides'])
            ->linkToCrudAction('importSpreadsheet')
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-download')
            ->createAsGlobalAction();

        // exports
        $exportCsvAction = $this->getExportCsvAction();
        $exportXlsxAction = $this->getExportXlsxAction();

        $statsSpreadsheetAction = Action::new('statsSpreadsheet', 'Consultations', 'fas fa-chart-line')
            ->setHtmlAttributes(['title' => 'Consultations des aides affichées', 'target' => '_blank']) // titre
            ->linkToCrudAction('statsSpreadsheet')
            ->createAsGlobalAction();

        $batchAssociate = Action::new('batchAssociate', 'Associé')
            ->linkToCrudAction('batchAssociate');

        $batchAssociateKeyword = Action::new('batchAssociateKeyword', 'Associé Mot clés')
            ->linkToCrudAction('batchAssociateKeyword');

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $displayOnFront)
            ->add(Crud::PAGE_INDEX, $displayStats)
            ->add(Crud::PAGE_EDIT, $displayOnFront)
            ->add(Crud::PAGE_INDEX, $importSpreadsheetAction)
            ->add(Crud::PAGE_INDEX, $exportCsvAction)
            ->add(Crud::PAGE_INDEX, $exportXlsxAction)
            ->add(Crud::PAGE_INDEX, $statsSpreadsheetAction)
            ->addBatchAction($batchAssociate)
            ->addBatchAction($batchAssociateKeyword)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity()->getInstance() ?? null;
        $badgeNoBackerAssociate = '<span class="badge badge-warning">(pas de porteur associé)</span>';
        $badgeNoBackerValid = '<span class="badge badge-warning">(porteur associé non validé)</span>';
        $projectReferencesSuggestions = [];
        if ($entity instanceof Aid) {
            $projectReferencesSuggestions = $this->aidService->getSuggestedProjectReferences($entity);
        }
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
            ->setColumns(12);
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
            ->setHelp(
                'Le titre doit commencer par un verbe à l’infinitif pour que l’objectif '
                . 'de l’aide soit explicite vis-à-vis de ses bénéficiaires.'
            )
            ->setFormTypeOption('attr', ['maxlength' => 180])
            ->setColumns(12);

        $slugHelp = 'Laisser vide pour autoremplir.';
        if ($entity instanceof Aid) {
            $aidDuplicates = $this->aidService->getAidDuplicates($entity);
            if (!empty($aidDuplicates)) {
                $slugHelp .= '<div class="alert alert-danger">';
                $slugHelp .= '<p>Attention ! Nous avons trouvé des aides qui ressemblent à des doublons.</p>';
                $slugHelp .= '<ul>';
                foreach ($aidDuplicates as $aidDuplicate) {
                    $urlEditAid = $this->adminUrlGenerator
                        ->setController(AidCrudController::class)
                        ->setAction(Action::EDIT)
                        ->setEntityId($aidDuplicate->getId())
                        ->generateUrl();
                    $slugHelp .=
                        '<li><a href="'
                        . $urlEditAid
                        . '" target="_blank">'
                        . $aidDuplicate->getName()
                        . '</a></li>';
                }
                $slugHelp .= '</ul>';
                $slugHelp .= '</div>';
            }

            $urlRedirects = $this->managerRegistry->getRepository(UrlRedirect::class)->findBy([
                'newUrl' => '/' . $this->aidService->getUrl($entity, UrlGeneratorInterface::RELATIVE_PATH)
            ]);
            if (!empty($urlRedirects)) {
                $slugHelp .= '<div class="alert alert-danger">';
                $slugHelp .= '<p>Attention ! Nous avons trouvé des redirections qui pointent vers cette aide.</p>';
                $slugHelp .= '<ul>';
                foreach ($urlRedirects as $urlRedirect) {
                    $slugHelp .= '<li>' . $urlRedirect->getOldUrl() . '</li>';
                }
                $slugHelp .= '</ul>';
                $slugHelp .= '</div>';
            }
        }

        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr', ['autocomplete' => 'off'])
            ->setHelp($slugHelp)
            ->hideOnIndex()
            ->setColumns(12);


        yield TextField::new('nameInitial', 'Nom initial')
            ->setHelp('Comment cette aide s’intitule-t-elle au sein de votre structure ? Exemple : AAP Mob’Biodiv')
            ->hideOnIndex()
            ->setColumns(12);

        yield FormField::addFieldset('Associations');

        yield AssociationField::new('categories', 'Thématiques')
            ->setFormTypeOption('choice_label', function ($entity) {
                $return = '';
                if ($entity) {
                    if ($entity->getCategoryTheme()) {
                        $return .= $entity->getCategoryTheme()->getName() . ' > ';
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
            ->setFormTypeOption('query_builder', function ($repository) {
                return $repository->createQueryBuilder('pr')
                    ->orderBy('pr.name', 'ASC');
            })
            ->setColumns(12)
            ->hideOnIndex();
        yield ArrayField::new('projectReferences', 'Projets référents')
            ->formatValue(function ($value, $entity) {
                return implode('', array_map(function ($projectReference) {
                    return '- ' . $projectReference->getName() . '<br>';
                }, $value->toArray()));
            })
            ->onlyOnIndex();

        if ($entity instanceof Aid) {
            $entity->setProjectReferencesSuggestions($projectReferencesSuggestions);
            $help = !empty($projectReferencesSuggestions)
                ? 'Ces résultats sont proposés en fonction de ce que donne la recherche.'
                : 'Aucun projet référent suggéré pour cette aide.';

            yield CollectionCopyableField::new('projectReferencesSuggestions', 'Projets référents suggérés')
                ->setHelp($help)
                ->formatValue(function ($value) {
                    return implode('', array_map(function ($projectReference) {
                        return '- ' . $projectReference->getName() . '<br>';
                    }, $value->toArray()));
                })
                ->setFormTypeOption('allow_add', false)
                ->setFormTypeOption('allow_delete', false)
                ->setColumns(12);
        }

        yield CollectionField::new('projectReferenceMissings', 'Projets référents manquants')
            ->setEntryType(ProjectReferenceMissingCreateType::class)
            ->allowAdd() // Permet d'ajouter de nouveaux éléments
            ->allowDelete() // Permet de supprimer des éléments
            ->setFormTypeOption('by_reference', false)
            ->setColumns(12)
            ->hideOnIndex();


        yield ArrayField::new('keywordReferences', 'Mots clés référents')
            ->formatValue(function ($value, $entity) {
                return implode('', array_map(function ($keywordReference) {
                    return '- ' . $keywordReference->getName() . '<br>';
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
                        $label .= ' (' . $entity->getParent()->getName() . ')';
                    }
                    return $label;
                }
            ])
            ->hideOnIndex()
            ->setColumns(12);
        yield AssociationField::new('aidAudiences', 'Bénéficiaires de l’aide')
            ->setFormTypeOption('choice_label', function ($entity) {
                $return = '';
                if ($entity) {
                    if ($entity->getOrganizationTypeGroup()) {
                        $return .= $entity->getOrganizationTypeGroup()->getName() . ' > ';
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
            ->setColumns(12);
        yield AssociationField::new('programs', 'Programmes')
            ->autocomplete()
            ->hideOnIndex()
            ->setColumns(12);
        yield AssociationField::new('aidTypes', 'Types d\'aide')
            ->setFormTypeOption('choice_label', function ($entity) {
                $return = '';
                if ($entity) {
                    if ($entity->getAidTypeGroup()) {
                        $return .= $entity->getAidTypeGroup()->getName() . ' > ';
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
                if ($value instanceof Perimeter) {
                    $name = $value->getName();
                    if ($value->getScale() == Perimeter::SCALE_COUNTY) {
                        $name .= ' (Département)';
                    } elseif ($value->getScale() == Perimeter::SCALE_REGION) {
                        $name .= ' (Région)';
                    } elseif ($value->getScale() == Perimeter::SCALE_COMMUNE) {
                        $name .= ' (Commune)';
                    } elseif ($value->getScale() == Perimeter::SCALE_ADHOC) {
                        $name .= ' (Adhoc)';
                    }
                    $display = strlen($name) < 20 ? $name : substr($name, 0, 20) . '...';
                    return sprintf('<span title="%s">%s</span>', $name, $display);
                } else {
                    return '';
                }
            });

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
        yield TrumbowygField::new(
            'projectExamples',
            'Exemples d’applications ou de projets réalisés grâce à cette aide'
        )
            ->setHelp(
                'Afin d’aider les territoires à mieux comprendre votre aide, '
                    . 'donnez ici quelques exemples concrets de projets réalisables ou réalisés.'
            )
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

        $hasNoBacker =
        (
            $entity
            && $entity->getOrganization()
            && !$entity->getOrganization()->getBacker()
        ) ? true : false;
        $hasBackerNotValide =
        (
            $entity
            && $entity->getOrganization()
            && $entity->getOrganization()->getBacker()
            && !$entity->getOrganization()->getBacker()->isActive()
        ) ? true : false;
        $symbol = ($hasNoBacker || $hasBackerNotValide) ? ' ' . $this->symbolWarning : '';
        yield FormField::addTab('Auteur' . $symbol);

        $currentAction = $this->getContext()->getCrud()->getCurrentAction();
        $label = 'Structure';

        if ($currentAction == Crud::PAGE_EDIT && $hasNoBacker) {
            $label .= ' ' . $badgeNoBackerAssociate;
        } elseif ($currentAction == Crud::PAGE_EDIT && $hasBackerNotValide) {
            $label .= ' ' . $badgeNoBackerValid;
        }
        yield AssociationField::new('organization', $label)
            ->autocomplete()
            ->formatValue(function ($value) use ($badgeNoBackerAssociate, $badgeNoBackerValid) {
                $return = $value ? $value->getName() : '';
                if ($value && !$value->getBacker()) {
                    $return .= ' ' . $badgeNoBackerAssociate;
                } elseif ($value && $value->getBacker() && !$value->getBacker()->isActive()) {
                    $return .= ' ' . $badgeNoBackerValid;
                }
                return $return;
            })
            ->setFormTypeOptions([
                'label_html' => true
            ]);

        yield AssociationField::new('author', 'Auteur')
            ->autocomplete()
            ->hideOnIndex();
        $nbAidsLive = 0;
        if ($entity && $entity->getAuthor()) {
            /** @var AidRepository $aidRepository */
            $aidRepository = $this->managerRegistry->getRepository(Aid::class);
            $nbAidsLive = $aidRepository->countCustom([
                'author' => $entity->getAuthor(),
                'showInSearch' => true
            ]);
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
        $symbol = ($entity && $entity->getAidFinancers()->isEmpty()) ? ' ' . $this->symbolWarning : '';
        yield FormField::addTab('Porteurs' . $symbol);
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
            ->hideOnIndex();
        yield CollectionField::new('aidFinancers', 'Porteurs d\'aides')
            ->useEntryCrudForm(AidFinancerAddBackerToAidCrudController::class)
            ->setColumns(12)
            ->formatValue(function ($value) {
                return implode('<br>', $value->toArray());
            });


        yield FormField::addFieldset('Porteurs d\'aides suggérés');
        yield TextField::new('financerSuggestion', 'Porteurs suggérés')
            ->setHelp(
                'Ce porteur a été suggéré. '
                . 'Créez le nouveau porteur et ajouter le en tant que porteur d’aides via le champ approprié.'
            )
            ->hideOnIndex()
            ->setColumns(12);

        yield FormField::addFieldset('Instructeurs');
        yield CollectionField::new('aidInstructors', 'Instructeurs')
            ->useEntryCrudForm(AidInstructorAddBackerToAidCrudController::class)
            ->setColumns(12)
            ->formatValue(function ($value) {
                return implode('<br>', $value->toArray());
            })
            ->hideOnIndex();

        yield FormField::addFieldset('Instructeurs suggérés');
        yield TextField::new('instructorSuggestion', 'Instructeurs suggérés')
            ->setHelp(
                'Cet instructeur a été suggéré. '
                . 'Créez le nouveau porteur et ajouter le en tant qu’instructeur via le champ approprié.'
            )
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
            ->setHelp(
                'Ne pas cocher pour les aides sous adhésion et ajouter la mention '
                    . ' « *sous adhésion » dans les critères d’éligibilité.'
            )
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


        yield FormField::addFieldset('Uniquement pour les aides génériques');
        yield BooleanField::new('isGeneric', 'Aide générique')
            ->setHelp('Cette aide est-elle générique ?')
            ->hideOnIndex()
            ->setColumns(12);
        yield AssociationField::new('sanctuarizedFields', 'Champs sanctuarisés')
            ->setFormTypeOptions([
                'by_reference' => false,
                'multiple' => true,
            ])
            ->hideOnIndex();

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
            ->setColumns(12);

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


        yield BooleanField::new('contactInfoUpdated', 'En attente de revue des données de contact mises à jour')
            ->setHelp('Cette aide est en attente d’une revue des données de contact')
            ->hideOnIndex()
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


    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->overrideTemplate('crud/edit', 'admin/aid/edit.html.twig')
            ->overrideTemplate('crud/index', 'admin/aid/index.html.twig')
            ->setPaginatorPageSize(50)
        ;
    }

    public function importSpreadsheet(AdminContext $context, FileService $fileService): Response
    {
        $form = $this->createForm(AidImportType::class);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // détermine l'extension
                $extension = $fileService->getExtension($form->get('file')->getData()->getClientOriginalName());
                switch ($extension) {
                    case 'csv':
                        $options = new OptionsCsv();
                        $options->FIELD_DELIMITER = ';';
                        $options->FIELD_ENCLOSURE = '"';

                        $reader = new ReaderCsv($options);

                        break;
                    case 'xlsx':
                        $reader = new ReaderXlsx();
                        break;
                    default:
                        $this->addFlash('danger', 'Le format de fichier n\'est pas supporté.');
                        return $this->render('admin/aid/import.html.twig', [
                            'form' => $form->createView(),
                        ]);
                }

                $reader->open($form->get('file')->getData()->getPathname());

                /** @var ProgramRepository $programRepository */
                $programRepository = $this->managerRegistry->getRepository(Program::class);

                /** @var BackerRepository $backerRepository */
                $backerRepository = $this->managerRegistry->getRepository(Backer::class);

                /** @var OrganizationTypeRepository $organizationTypeRepository */
                $organizationTypeRepository = $this->managerRegistry->getRepository(OrganizationType::class);

                /** @var AidTypeRepository $aidTypeRepository */
                $aidTypeRepository = $this->managerRegistry->getRepository(AidType::class);

                /** @var CategoryRepository $categoryRepository */
                $categoryRepository = $this->managerRegistry->getRepository(Category::class);

                /** @var AidRecurrenceRepository $aidRecurrenceRepository */
                $aidRecurrenceRepository = $this->managerRegistry->getRepository(AidRecurrence::class);

                /** @var AidStepRepository $aidStepRepository */
                $aidStepRepository = $this->managerRegistry->getRepository(AidStep::class);

                /** @var AidDestinationRepository $aidDestinationRepository */
                $aidDestinationRepository = $this->managerRegistry->getRepository(AidDestination::class);

                /** @var PerimeterRepository $perimeterRepository */
                $perimeterRepository = $this->managerRegistry->getRepository(Perimeter::class);

                /** @var UserRepository $userRepository */
                $userRepository = $this->managerRegistry->getRepository(User::class);

                /** @var OrganizationRepository $organizationRepository */
                $organizationRepository = $this->managerRegistry->getRepository(Organization::class);

                /** @var ProjectReferenceRepository $projectReferenceRepository */
                $projectReferenceRepository = $this->managerRegistry->getRepository(ProjectReference::class);

                $importReturns = [];
                $lineNumber = 1;
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        // on passe la permière ligne (entêtes)
                        if ($lineNumber == 1) {
                            $lineNumber++;
                            continue;
                        }
                        // do stuff with the row
                        $cells = $row->getCells();

                        try {
                            $aid = new Aid();
                            $aid->setName(trim($cells[0]->getValue()) !== '' ? trim($cells[0]->getValue()) : null);

                            $programNames = explode(',', $cells[1]->getValue());
                            foreach ($programNames as $programName) {
                                $program = $programRepository->findOneBy(['name' => trim($programName)]);
                                if ($program instanceof Program) {
                                    $aid->addProgram($program);
                                }
                            }
                            $aid->setNameInitial($cells[2]->getValue());
                            $backerNames = explode(',', $cells[3]->getValue());
                            foreach ($backerNames as $backerName) {
                                $backer = $backerRepository->findOneBy(['name' => trim($backerName)]);
                                if ($backer instanceof Backer) {
                                    $aidFinancer = new AidFinancer();
                                    $aidFinancer->setBacker($backer);
                                    $aid->addAidFinancer($aidFinancer);
                                }
                            }

                            $instructorNames = explode(',', $cells[4]->getValue());
                            foreach ($instructorNames as $instructorName) {
                                $backer = $backerRepository->findOneBy(['name' => trim($instructorName)]);
                                if ($backer instanceof Backer) {
                                    $aidInstructor = new AidInstructor();
                                    $aidInstructor->setBacker($backer);
                                    $aid->addAidInstructor($aidInstructor);
                                }
                            }
                            $audienceNames = explode(',', $cells[5]->getValue());
                            foreach ($audienceNames as $audienceName) {
                                $organizationType = $organizationTypeRepository
                                    ->findOneBy(['name' => trim($audienceName)]);
                                if ($organizationType instanceof OrganizationType) {
                                    $aid->addAidAudience($organizationType);
                                }
                            }
                            $aidTypeNames = explode(',', $cells[6]->getValue());
                            foreach ($aidTypeNames as $aidTypeName) {
                                $aidType = $aidTypeRepository->findOneBy(['name' => trim($aidTypeName)]);
                                if ($aidType instanceof AidType) {
                                    $aid->addAidType($aidType);
                                }
                            }
                            $subventionsRates = explode(',', $cells[7]->getValue());
                            $aid->setSubventionRateMin(isset($subventionsRates[0]) ? (int) $subventionsRates[0] : null);
                            $aid->setSubventionRateMax(isset($subventionsRates[1]) ? (int) $subventionsRates[1] : null);
                            $aid->setSubventionComment(trim($cells[8]->getValue()) !== ''
                            ? trim($cells[8]->getValue()) : null);

                            $aid->setIsCallForProject(
                                trim(strtolower($cells[9]->getValue())) == 'oui' ? true : false
                            );
                            $aid->setDescription(trim($cells[10]->getValue()) !== ''
                                ? nl2br(trim($cells[10]->getValue())) : null);
                            $aid->setProjectExamples(trim($cells[11]->getValue()) !== ''
                                ? nl2br(trim($cells[11]->getValue())) : null);

                            $categoryNames = explode(',', $cells[12]->getValue());
                            foreach ($categoryNames as $categoryName) {
                                $category = $categoryRepository->findOneBy(['name' => trim($categoryName)]);
                                if ($category instanceof Category) {
                                    $aid->addCategory($category);
                                }
                            }

                            $recurrenceName = (trim($cells[13]->getValue()) !== ''
                                ? trim($cells[13]->getValue()) : null);
                            if ($recurrenceName) {
                                $recurrence = $aidRecurrenceRepository->findOneBy(['name' => $recurrenceName]);
                                if ($recurrence instanceof AidRecurrence) {
                                    $aid->setAidRecurrence($recurrence);
                                }
                            }

                            $dateStartFromCell = $cells[14]->getValue();
                            if (!$dateStartFromCell instanceof DateTimeImmutable) {
                                $dateStartFromCell = trim($cells[14]->getValue()) !== ''
                                    ? new \DateTime($cells[14]->getValue()) : null;
                            }
                            $aid->setDateStart($dateStartFromCell);
                            $dateSubmissionDeadlineFromCell = $cells[15]->getValue();
                            if (!$dateSubmissionDeadlineFromCell instanceof DateTimeImmutable) {
                                $dateSubmissionDeadlineFromCell = trim($cells[15]->getValue()) !== ''
                                    ? new \DateTime($cells[15]->getValue()) : null;
                            }
                            $aid->setDateSubmissionDeadline($dateSubmissionDeadlineFromCell);

                            $aid->setEligibility(trim($cells[16]->getValue()) !== ''
                                ? nl2br(trim($cells[16]->getValue())) : null);

                            $aidStepNames = explode(',', $cells[17]->getValue());
                            foreach ($aidStepNames as $aidStepName) {
                                $aidStep = $aidStepRepository
                                    ->findOneBy(['name' => trim($aidStepName)]);
                                if ($aidStep instanceof AidStep) {
                                    $aid->addAidStep($aidStep);
                                }
                            }

                            $aidDestinationNames = explode(',', $cells[18]->getValue());
                            foreach ($aidDestinationNames as $aidDestinationName) {
                                $aidDestination = $aidDestinationRepository
                                    ->findOneBy(['name' => trim($aidDestinationName)]);
                                if ($aidDestination instanceof AidDestination) {
                                    $aid->addAidDestination($aidDestination);
                                }
                            }

                            $perimeterId = (int) trim($cells[19]->getValue());
                            if ($perimeterId) {
                                $perimeter = $perimeterRepository->find($perimeterId);
                                if ($perimeter instanceof Perimeter) {
                                    $aid->setPerimeter($perimeter);
                                }
                            }

                            $aid->setOriginUrl(trim($cells[20]->getValue()) !== ''
                                ? trim($cells[20]->getValue()) : null);
                            $aid->setApplicationUrl(trim($cells[21]->getValue()) !== ''
                                ? trim($cells[21]->getValue()) : null);

                            $aid->setContact(trim($cells[22]->getValue()) !== ''
                                ? nl2br(trim($cells[22]->getValue())) : null);

                            $userEmail = trim($cells[23]->getValue());
                            if ($userEmail !== '') {
                                $user = $userRepository->findOneBy(['email' => $userEmail]);
                                if ($user instanceof User) {
                                    $aid->setAuthor($user);
                                }
                            }

                            $aid->setImportDataUrl(trim($cells[24]->getValue()) !== ''
                                ? trim($cells[24]->getValue()) : null);

                            $organizationId = (int) trim($cells[25]->getValue());
                            if ($organizationId) {
                                $organization = $organizationRepository->find($organizationId);
                                if ($organization instanceof Organization) {
                                    $aid->setOrganization($organization);
                                }
                            }

                            $projectReferenceNames = explode(',', $cells[26]->getValue());
                            foreach ($projectReferenceNames as $projectReferenceName) {
                                $projectReference = $projectReferenceRepository
                                ->findOneBy(['name' => trim($projectReferenceName)]);
                                if ($projectReference instanceof ProjectReference) {
                                    $aid->addProjectReference($projectReference);
                                }
                            }

                            // status forcé
                            $aid->setStatus(Aid::STATUS_REVIEWABLE);

                            // sauvegarde
                            $this->managerRegistry->getManager()->persist($aid);
                            $this->managerRegistry->getManager()->flush();

                            $importReturns[] = [
                                'row' => $lineNumber,
                                'aidName' => $cells[0]->getValue(),
                                'aidId' => $aid->getId(),
                                'urlEdit' => $this->adminUrlGenerator
                                    ->setController(AidCrudController::class)
                                    ->setAction(Action::EDIT)
                                    ->setEntityId($aid->getId())
                                    ->generateUrl(),
                                'status' => 'success',
                            ];

                            $lineNumber++;
                        } catch (TypeError $e) {
                            $importReturns[] = [
                                'row' => $lineNumber,
                                'aidName' => $cells[0]->getValue(),
                                'status' => 'error',
                                'message' => 'Erreur de type : ' . $e->getMessage(),
                            ];

                            $lineNumber++;
                        } catch (\Exception $e) {
                            $importReturns[] = [
                                'row' => $lineNumber,
                                'aidName' => $cells[0]->getValue(),
                                'status' => 'error',
                                'message' => $e->getMessage(),
                            ];

                            $lineNumber++;
                        }
                    }
                }

                $reader->close();
                // traitement des données
                // $reader = new Reader($form->get('file')->getData()->getPathname());
            } else {
                $this->addFlash('danger', 'Le formulaire contient des erreurs.');
            }
        }
        return $this->render('admin/aid/import.html.twig', [
            'form' => $form->createView(),
            'importReturns' => $importReturns ?? null
        ]);
    }

    public function exportXlsx(
        AdminContext $context,
        SpreadsheetExporterService $spreadsheetExporterService,
        string $filename = 'aides'
    ): Response {
        return $this->exportSpreadsheet($context, $spreadsheetExporterService, $filename, 'xlsx');
    }

    public function exportCsv(
        AdminContext $context,
        SpreadsheetExporterService $spreadsheetExporterService,
        string $filename = 'aides'
    ): Response {
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

    public function statsSpreadsheet(
        AdminContext $context,
        SpreadsheetExporterService $spreadsheetExporterService,
        string $filename = 'consultations-aides',
        string $format = FileService::FORMAT_XLSX
    ): Response
    {
        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create(
            $context->getCrud()->getFiltersConfig(),
            $fields,
            $context->getEntity()
        );
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);

        return $spreadsheetExporterService->createResponseFromQueryBuilder(
            $queryBuilder,
            get_class(new LogAidView()), // on utilise l'entite logAidView pour l'export
            $filename,
            $format
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['handleProjectReferenceMissingPersistEvent'],
            BeforeEntityUpdatedEvent::class => ['handleProjectReferenceMissingUpdateEvent'],
        ];
    }

    public function handleProjectReferenceMissingPersistEvent(BeforeEntityPersistedEvent $event): void
    {
        if ($event->getEntityInstance() instanceof Aid) {
            $this->handleProjectReferenceMissingDuplicates($event->getEntityInstance());
        }
    }

    public function handleProjectReferenceMissingUpdateEvent(BeforeEntityUpdatedEvent $event): void
    {
        if ($event->getEntityInstance() instanceof Aid) {
            $this->handleProjectReferenceMissingDuplicates($event->getEntityInstance());
        }
    }

    private function handleProjectReferenceMissingDuplicates(Aid $entity): void
    {
        // on parcours les projets référents manquants donnés
        foreach ($entity->getProjectReferenceMissings() as $projectReferenceMissing) {
            // si l'id est null
            if (!$projectReferenceMissing->getId()) {
                // on vérifie que ce projet n'existe pas déjà par le nom
                $projectReferenceMissingTest = $this->managerRegistry
                    ->getRepository(ProjectReferenceMissing::class)
                    ->findOneBy(['name' => $projectReferenceMissing->getName()]);
                // si il existe déjà
                if ($projectReferenceMissingTest instanceof ProjectReferenceMissing) {
                    // on ajoute à l'entité
                    $entity->addProjectReferenceMissing($projectReferenceMissingTest);
                    // on retire l'ajout sans id pour éviter les doublons
                    $entity->removeProjectReferenceMissing($projectReferenceMissing);
                }
                $this->managerRegistry->getManager()->remove($projectReferenceMissing);
            }
        }
    }
}
