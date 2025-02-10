<?php

namespace App\Form\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerGroup;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use App\Entity\Organization\OrganizationType;
use App\Entity\Program\Program;
use App\Entity\Search\SearchPage;
use App\Form\Type\BackerAutocompleteType;
use App\Form\Type\EntityCheckboxAbsoluteType;
use App\Form\Type\EntityCheckboxGroupAbsoluteType;
use App\Form\Type\PerimeterAutocompleteType;
use App\Repository\Backer\BackerGroupRepository;
use App\Service\Aid\AidSearchClass;
use App\Service\Aid\AidSearchFormService;
use App\Service\Site\AbTestService;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class AidSearchTypeV2 extends AbstractType
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private RouterInterface $routerInterface,
        private AbTestService $abTestService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // ab test vapp
        $abTestVapp = $this->abTestService->shouldShowTestVersion();

        // les catégories
        $categoryThemes = $this->managerRegistry->getRepository(CategoryTheme::class)->findBy(
            [],
            [
                'name' => 'ASC',
            ]
        );
        $categoriesByTheme = [];
        foreach ($categoryThemes as $categoryTheme) {
            if (!isset($categoriesByTheme[$categoryTheme->getName()])) {
                $categoriesByTheme[$categoryTheme->getName()] = [];
            }
            foreach ($categoryTheme->getCategories() as $category) {
                $categoriesByTheme[$categoryTheme->getName()][] = [$category->getName() => $category->getId()];
            }
        }

        // Builder
        $builder
            ->add(AidSearchFormService::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG, EntityType::class, [
                'required' => $abTestVapp ? true : false,
                'label' => 'Vous cherchez pour…',
                'class' => OrganizationType::class,
                'choice_label' => 'name',
                'choice_value' => function (?OrganizationType $entity) {
                    return $entity ? $entity->getSlug() : '';
                },
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('ot');

                    if (
                        $options['searchPage'] instanceof SearchPage
                        && !$options['searchPage']->getOrganizationTypes()->isEmpty()
                    ) {
                        $qb->andWhere('ot IN (:organizationTypes)')
                            ->setParameter('organizationTypes', $options['searchPage']->getOrganizationTypes());
                    }

                    return $qb;
                },
                'placeholder' => 'Tous types de structures',
            ])
            ->add(AidSearchFormService::QUERYSTRING_KEY_SEARCH_PERIMETER, PerimeterAutocompleteType::class, [
                'required' => $abTestVapp ? true : false,
                'label' => 'Votre territoire',
                'label_attr' => [
                    'id' => 'label-perimeter-search',
                ],
                'attr' => [
                    'data-controller' => 'custom-autocomplete',
                    'placeholder' => 'Votre commune, EPCI...',
                    'aria-hidden' => 'true',
                    'aria-labelledby' => 'label-perimeter-search',
                ],
            ])
            ->add(AidSearchFormService::QUERYSTRING_KEY_KEYWORD, TextType::class, [
                'required' => false,
                'label' => 'Projet référent ou mot-clé',
                'label_attr' => [
                    'id' => 'label-keyword-search',
                ],
                'attr' => [
                    'data-controller' => 'custom-autocomplete',
                    'placeholder' => 'Projet référent ou mot-clé',
                    'aria-labelledby' => 'label-keyword-search',
                    'aria-hidden' => 'true',
                ],
                'autocomplete' => true,
                'autocomplete_url' => $this->routerInterface->generate('app_project_reference_ajax_ux_autocomplete'),
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'maxItems' => 1,
                    'selectOnTab' => true,
                    'closeAfterSelect' => true,
                    'sanitize_html' => true,
                    'delimiter' => '$%§',
                ],
                'sanitize_html' => true,
            ])
            ->add(AidSearchFormService::QUERYSTRING_KEY_CATEGORY_IDS, EntityCheckboxGroupAbsoluteType::class, [
                'required' => false,
                'label' => 'Thématiques de l\'aide',
                'placeholder' => 'Toutes les sous-thématiques',
                'class' => Category::class,
                'choice_label' => 'name',
                'group_by' => function (Category $category) {
                    return $category->getCategoryTheme()->getName();
                },
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('c')
                        ->innerJoin('c.categoryTheme', 'categoryTheme')
                        ->orderBy('categoryTheme.name', 'ASC')
                        ->addOrderBy('c.name', 'ASC');
                    if (
                        $options['searchPage'] instanceof SearchPage
                        && !$options['searchPage']->getCategories()->isEmpty()
                    ) {
                        $qb->andWhere('c IN (:categories)')
                            ->setParameter('categories', $options['searchPage']->getCategories());
                    }

                    return $qb;
                },
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('newIntegration', HiddenType::class)
        ;

        if ($options['extended']) {
            // les types d'aides
            $aidTypeGroups = $this->managerRegistry->getRepository(AidTypeGroup::class)->findBy(
                [],
                [
                    'position' => 'ASC',
                ]
            );
            $aidTypesByGroup = [];
            foreach ($aidTypeGroups as $aidTypeGroup) {
                if (!isset($aidTypesByGroup[$aidTypeGroup->getName()])) {
                    $aidTypesByGroup[$aidTypeGroup->getName()] = [];
                }
                foreach ($aidTypeGroup->getAidTypes() as $aidType) {
                    $aidTypesByGroup[$aidTypeGroup->getName()][] = [$aidType->getName() => $aidType->getId()];
                }
            }

            // le builder
            $builder
                ->add(AidSearchFormService::QUERYSTRING_KEY_ORDER_BY, ChoiceType::class, [
                    'required' => true,
                    'label' => false,
                    'choices' => [
                        'Tri : pertinence' => 'relevance',
                        'Tri : date de publication (plus récentes en premier)' => 'publication-date',
                        'Tri : date de clôture (plus proches en premier)' => 'submission-deadline',
                    ],
                    'attr' => [
                        'title' => 'Choisissez un ordre de tri – La sélection recharge la page',
                    ],
                ])
                ->add(AidSearchFormService::QUERYSTRING_KEY_AID_TYPE_IDS, EntityCheckboxGroupAbsoluteType::class, [
                    'required' => false,
                    'label' => 'Nature de l\'aide',
                    'placeholder' => 'Toutes les natures d\'aide',
                    'class' => AidType::class,
                    'choice_label' => 'name',
                    'group_by' => function (AidType $aidType) {
                        return $aidType->getAidTypeGroup()->getName();
                    },
                    'query_builder' => function (EntityRepository $entityRepository) {
                        return $entityRepository
                            ->createQueryBuilder('at')
                            ->innerJoin('at.aidTypeGroup', 'aidTypeGroup')
                            ->orderBy('aidTypeGroup.name', 'ASC')
                            ->addorderBy('at.name', 'ASC')
                        ;
                    },
                    'multiple' => true,
                    'expanded' => true,
                ])
                ->add(AidSearchFormService::QUERYSTRING_KEY_BACKER_IDS, BackerAutocompleteType::class, [
                    'required' => false,
                    'label' => 'Porteurs d\'aides',
                    'label_attr' => [
                        'id' => 'label-backer-search',
                    ],
                    'help' => 'Filtré par le champ "Votre territoire"',
                    'class' => Backer::class,
                    'choice_label' => 'name',
                    'attr' => [
                        'data-controller' => 'backer-autocomplete',
                        'placeholder' => 'Tous les porteurs d\'aides',
                        'aria-labelledby' => 'label-backer-search',
                    ],
                    'autocomplete' => true,
                    'multiple' => true,
                ])
                ->add(AidSearchFormService::QUERYSTRING_KEY_APPLY_BEFORE, DateType::class, [
                    'required' => false,
                    'label' => 'Candidater avant...',
                    'widget' => 'single_text',
                ])
                ->add('programs', EntityCheckboxAbsoluteType::class, [
                    'required' => false,
                    'label' => 'Programmes d\'aides',
                    'placeholder' => 'Tous les programmes',
                    'class' => Program::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                    'query_builder' => function (EntityRepository $entityRepository) {
                        return $entityRepository->createQueryBuilder('b')->orderBy('b.name', 'ASC');
                    },
                ])
                ->add(AidSearchFormService::QUERYSTRING_KEY_AID_STEP_IDS, EntityCheckboxAbsoluteType::class, [
                    'required' => false,
                    'label' => 'Avancement du projet',
                    'placeholder' => 'Toutes les étapes',
                    'class' => AidStep::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                ])
                ->add(AidSearchFormService::QUERYSTRING_KEY_AID_DESTINATION_IDS, EntityCheckboxAbsoluteType::class, [
                    'required' => false,
                    'label' => 'Actions concernées',
                    'placeholder' => 'Tous les types de dépenses',
                    'class' => AidDestination::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                ])
                ->add(AidSearchFormService::QUERYSTRING_KEY_IS_CHARGED, ChoiceType::class, [
                    'required' => false,
                    'label' => 'Aides payantes ou gratuites',
                    'placeholder' => 'Aides gratuites et payantes',
                    'choices' => [
                        'Aides payantes' => 1,
                        'Aides gratuites' => 0,
                    ],
                ])
                ->add(AidSearchFormService::QUERYSTRING_KEY_EUROPEAN_AID_SLUG, ChoiceType::class, [
                    'required' => false,
                    'label' => 'Aides européennes ?',
                    'placeholder' => 'Aides européennes ou non',
                    'choices' => [
                        Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN] => Aid::SLUG_EUROPEAN,
                        Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_SECTORIAL] => Aid::SLUG_EUROPEAN_SECTORIAL,
                        Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_ORGANIZATIONAL] => Aid::SLUG_EUROPEAN_ORGANIZATIONAL,
                    ],
                ])
                ->add(AidSearchFormService::QUERYSTRING_KEY_IS_CALL_FOR_PROJECT, CheckboxType::class, [
                    'required' => false,
                    'label' => 'Appels à projets / Appels à manifestation d’intérêt uniquement',
                ])
                ->add(AidSearchFormService::QUERYSTRING_KEY_BACKER_GROUP_ID, EntityType::class, [
                    'required' => false,
                    'label' => 'Groupe de porteurs d\'aides',
                    'label_attr' => [
                        'id' => 'label-backer-group-search',
                    ],
                    'class' => BackerGroup::class,
                    'choice_label' => 'name',
                    'attr' => [
                        'placeholder' => 'Tous les groupes de porteurs d\'aides',
                        'aria-labelledby' => 'label-backer-group-search',
                    ],
                    'autocomplete' => true,
                    'multiple' => false,
                    'query_builder' => function (BackerGroupRepository $backerGroupRepository) {
                        return $backerGroupRepository->getQueryBuilder([
                            'orderBy' => [
                                'sort' => 'bg.name',
                                'order' => 'ASC',
                            ],
                        ]);
                    },
                ])
            ;

            foreach ($options['removes'] as $remove) {
                if ($builder->has($remove)) {
                    $builder->remove($remove);
                }
            }
        }

        if ($abTestVapp) {
            $builder->add('vapp_description', TextareaType::class, [
                'label' => 'Description de votre projet',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Exemple: Assistance à maitrise d’ouvrage pour l’Installation de panneaux photovoltaiques sur le toit de l’école municipale.',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AidSearchClass::class,
            'attr' => [
                'data-controller' => 'custom-autocomplete',
            ],
            'allow_extra_fields' => true,
            'extended' => false,
            'removes' => [],
            'searchPage' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
