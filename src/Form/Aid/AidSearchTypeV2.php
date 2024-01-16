<?php

namespace App\Form\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use App\Entity\Organization\OrganizationType;
use App\Entity\Program\Program;
use App\Service\User\UserService;
use App\Form\Type\EntityCheckboxAbsoluteType;
use App\Form\Type\EntityCheckboxGroupAbsoluteType;
use App\Form\Type\PerimeterAutocompleteType;
use App\Service\Aid\AidSearchClass;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class AidSearchTypeV2 extends AbstractType
{
    public function  __construct(
        private UserService $userService,
        protected ManagerRegistry $managerRegistry,
        protected RouterInterface $routerInterface
    )
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // les catégories
        $categoryThemes = $this->managerRegistry->getRepository(CategoryTheme::class)->findBy(
            [],
            [
                'name' => 'ASC'
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
            ->add('organizationType', EntityType::class, [
                'required' => false,
                'label' => 'Vous cherchez pour…',
                'class' => OrganizationType::class,
                'choice_label' => 'name',
                'choice_value' => function (?OrganizationType $entity) {
                    return $entity ? $entity->getSlug() : '';
                },
                'placeholder' => 'Tous types de structures',
            ])
            ->add('searchPerimeter', PerimeterAutocompleteType::class, [
                'required' => false,
                'label' => 'Votre territoire',
                'attr' => [
                    'data-controller' => 'custom-autocomplete',
                    'placeholder' => 'Votre commune, EPCI...'
                ]
            ])
            ->add('keyword', TextType::class, [
                'required' => false,
                'label' => 'Mot-clés',
                'attr' => [
                    'data-controller' => 'custom-autocomplete'
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
                    'delimiter' => '$%§'
                ]

            ])
            ->add('categorysearch', EntityCheckboxGroupAbsoluteType::class, [
                'required' => false,
                'label' => 'Thématiques de l\'aide',
                'placeholder' => 'Toutes les sous-thématiques',
                'help' => 'Sélectionnez la ou les thématiques associées à votre aide. N’hésitez pas à en choisir plusieurs.',
                'class' => Category::class,
                'choice_label' => 'name',
                'group_by' => function(Category $category) {
                    return $category->getCategoryTheme()->getName();
                },
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->innerJoin('c.categoryTheme', 'categoryTheme')
                        ->orderBy('categoryTheme.name', 'ASC')
                        ->addOrderBy('c.name', 'ASC')
                    ;
                },
                'multiple' => true,
                'expanded' => true
            ])
            ->add('newIntegration', HiddenType::class)
        ;


        if ($options['extended']) {
            // les types d'aides
            $aidTypeGroups = $this->managerRegistry->getRepository(AidTypeGroup::class)->findBy(
                [],
                [
                    'position' => 'ASC'
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
                ->add('orderBy', ChoiceType::class, [
                    'required' => true,
                    'label' => false,
                    'choices' => [
                        'Tri : pertinence' => 'relevance',
                        'Tri : date de publication (plus récentes en premier)' => 'publication_date',
                        'Tri : date de clôture (plus proches en premier)' => 'submission_deadline'
                    ],
                    'attr' => [
                        'title' => 'Choisissez un ordre de tri – La sélection recharge la page'
                    ]
                ])
                ->add('aidTypes', EntityCheckboxGroupAbsoluteType::class, [
                    'required' => false,
                    'label' => 'Nature de l\'aide',
                    'placeholder' => 'Toutes les natures d\'aide',
                    'class' => AidType::class,
                    'choice_label' => 'name',
                    'group_by' => function(AidType $aidType) {
                        return $aidType->getAidTypeGroup()->getName();
                    },
                    'multiple' => true,
                    'expanded' => true
                ])
                ->add('backers', EntityType::class, [
                    'required' => false,
                    'label' => 'Porteurs d\'aides',
                    'class' => Backer::class,
                    'choice_label' => 'name',
                    'attr' => [
                        'placeholder' => 'Tous les porteurs d\'aides',
                    ],
                    'autocomplete' => true,
                    'multiple' => true,
                    'query_builder' => function(EntityRepository $entityRepository) {
                        return $entityRepository->createQueryBuilder('b')->orderBy('b.name', 'ASC');
                    }
                ])
                ->add('applyBefore', DateType::class, [
                    'required' => false,
                    'label' => 'Candidater avant...',
                    'widget' => 'single_text'
                ])
                ->add('programs', EntityCheckboxAbsoluteType::class, [
                    'required' => false,
                    'label' => 'Programmes d\'aides',
                    'placeholder' => 'Tous les programmes',
                    'class' => Program::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                    'query_builder' => function(EntityRepository $entityRepository) {
                        return $entityRepository->createQueryBuilder('b')->orderBy('b.name', 'ASC');
                    }
                ])
                ->add('aidSteps', EntityCheckboxAbsoluteType::class, [
                    'required' => false,
                    'label' => 'Avancement du projet',
                    'placeholder' => 'Toutes les étapes',
                    'class' => AidStep::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true
                ])
                ->add('aidDestinations', EntityCheckboxAbsoluteType::class, [
                    'required' => false,
                    'label' => 'Actions concernées',
                    'placeholder' => 'Tous les types de dépenses',
                    'class' => AidDestination::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true
                ])
                ->add('isCharged', ChoiceType::class, [
                    'required' => false,
                    'label' => 'Aides payantes ou gratuites',
                    'placeholder' => 'Aides gratuites et payantes',
                    'choices' => [
                        'Aides payantes' => 1,
                        'Aides gratuites' => 0
                    ]
                ])
                ->add('europeanAid', ChoiceType::class, [
                    'required' => false,
                    'label' => 'Aides européennes ?',
                    'placeholder' => 'Aides européennes ou non',
                    'choices' => [
                        Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN] => Aid::SLUG_EUROPEAN,
                        Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_SECTORIAL] => Aid::SLUG_EUROPEAN_SECTORIAL,
                        Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_ORGANIZATIONAL] => Aid::SLUG_EUROPEAN_ORGANIZATIONAL,
                    ]
                ])
                ->add('isCallForProject', CheckboxType::class, [
                    'required' => false,
                    'label' => 'Appels à projets / Appels à manifestation d’intérêt uniquement'
                ])
                ;


            foreach ($options['removes'] as $remove) {
                if ($builder->has($remove)) {
                    $builder->remove($remove);
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AidSearchClass::class,
            'attr' => [
                'data-controller' => 'custom-autocomplete'
            ],
            'allow_extra_fields' => true,
            'extended' => false,
            'removes' => [],

        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
