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

class AidSearchType extends AbstractType
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

        
        /** @var User $user */
        $user = $this->userService->getUserLogged();

        // organizationType params
        $organizationTypeParams = [
            'required' => false,
            'label' => 'Vous cherchez pour…',
            'class' => OrganizationType::class,
            'choice_label' => 'name',
            'choice_value' => 'slug',
            'placeholder' => 'Tous types de structures',
        ];
        if ($options['forceOrganizationType'] !== false) {
            $organizationTypeParams['data'] = $options['forceOrganizationType'];
        } else {
            $organizationTypeParams['data'] = ($user && $user->getDefaultOrganization() && $user->getDefaultOrganization()->getOrganizationType()) ? $user->getDefaultOrganization()->getOrganizationType() : null;
        }

        // Perimeter params
        $perimeterParams = [
            'required' => false,
            'label' => 'Votre territoire'
        ];
        if ($options['forcePerimeter'] !== false) {
            $perimeterParams['data'] = $options['forcePerimeter'];
        } else {
            if (!$options['dontUseUserPerimeter']) {
                $perimeterParams['data'] = ($user && $user->getDefaultOrganization() && $user->getDefaultOrganization()->getPerimeter()) ? $user->getDefaultOrganization()->getPerimeter() : null;
            }
        }

        // keyword params
        $keywordParams = [
            'required' => false,
            'label' => 'Mot-clés'
        ];
        if ($options['forceKeyword'] !== false) {
            $keywordParams['data'] = $options['forceKeyword'];
        }
        $keywordParams['autocomplete'] = true;
        // $keywordParams['autocomplete_url'] = $this->routerInterface->generate('app_keyword_kewyord_synonymlist_ajax_autocomplete');
        $keywordParams['autocomplete_url'] = $this->routerInterface->generate('app_project_reference_ajax_ux_autocomplete');
        $keywordParams['tom_select_options'] =
            [
                'create' => true,
                'createOnBlur' => true,
                'maxItems' => 1,
                // 'addPrecedence' => true,
                // 'persist' => false,
                'selectOnTab' => true,
                'closeAfterSelect' => true,
                'sanitize_html' => true,
                'delimiter' => '$%§'
            ];
        // caregory search params
        $categorySearchParams = [
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
                ;
            },
            'multiple' => true,
            'expanded' => true
        ];
        if ($options['forceCategorySearch'] !== false) {
            $categorySearchParams['data'] = $options['forceCategorySearch'];
        }

        // Builder
        $builder
            ->add('organizationType', EntityType::class, $organizationTypeParams)
            ->add('searchPerimeter', PerimeterAutocompleteType::class, $perimeterParams)
            // ->add('keyword', KeywordSynonymlistAutocompleteField::class,  $keywordParams)
            ->add('keyword', TextType::class,  $keywordParams)
            // ->add('categorysearch', CheckboxMultipleSearchType::class, $categorySearchParams)
            ->add('categorysearch', EntityCheckboxGroupAbsoluteType::class, $categorySearchParams)
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
            // aid type params
            $aidTypeParams = [
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
            ];
            if ($options['forceAidTypes'] !== false) {
                $aidTypeParams['aidTypes'] = $options['forceAidTypes'];
            }
            
            $backersParams = [
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
            ];
            if ($options['forceBackers'] !== false) {
                $backersParams['data'] = $options['forceBackers'];
            }

            $applyBeforeParams = [
                'required' => false,
                'label' => 'Candidater avant…',
                'widget' => 'single_text'
            ];
            if ($options['forceApplyBefore'] !== false) {
                $applyBeforeParams['data'] = $options['forceApplyBefore'];
            }


            $aidStepsParams = [
                'required' => false,
                'label' => 'Avancement du projet',
                'placeholder' => 'Toutes les étapes',
                'class' => AidStep::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true
            ];
            if ($options['forceAidSteps'] !== false) {
                $aidStepsParams['data'] = $options['forceAidSteps'];
            }

            $aidDestinationsParams = [
                'required' => false,
                'label' => 'Actions concernées',
                'placeholder' => 'Tous les types de dépenses',
                'class' => AidDestination::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true
            ];
            if ($options['forceAidDestinations'] !== false) {
                $aidDestinationsParams['data'] = $options['forceAidDestinations'];
            }

            $isChargedParams = [
                'required' => false,
                'label' => 'Aides payantes ou gratuites',
                'placeholder' => 'Aides gratuites et payantes',
                'choices' => [
                    'Aides payantes' => 1,
                    'Aides gratuites' => 0
                ]
            ];
            if ($options['forceIsCharged'] !== false) {
                $isChargedParams['data'] = $options['forceIsCharged'];
            }

            $europeanAidParams = [
                'required' => false,
                'label' => 'Aides européennes ?',
                'placeholder' => 'Aides européennes ou non',
                'choices' => [
                    Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN] => Aid::SLUG_EUROPEAN,
                    Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_SECTORIAL] => Aid::SLUG_EUROPEAN_SECTORIAL,
                    Aid::LABELS_EUROPEAN[Aid::SLUG_EUROPEAN_ORGANIZATIONAL] => Aid::SLUG_EUROPEAN_ORGANIZATIONAL,
                ]
            ];
            if ($options['forceEuropeanAid'] !== false) {
                $europeanAidParams['data'] = $options['forceEuropeanAid'];
            }

            $isCallForProjectParams = [
                'required' => false,
                'label' => 'Appels à projets / Appels à manifestation d’intérêt uniquement'
            ];
            if ($options['forceIsCallForProject'] !== false) {
                $isCallForProjectParams['data'] = $options['forceIsCallForProject'];
            }

            $programsParams = [
                'required' => false,
                'label' => 'Programmes d\'aides',
                'placeholder' => 'Tous les programmes',
                'class' => Program::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true
            ];
            if ($options['forcePrograms'] !== false) {
                $programsParams['data'] = $options['forcePrograms'];
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
                ->add('aidTypes', EntityCheckboxGroupAbsoluteType::class, $aidTypeParams)
                ->add('backers', EntityType::class, $backersParams)
                ->add('applyBefore', DateType::class, $applyBeforeParams)
                ->add('programs', EntityCheckboxAbsoluteType::class, $programsParams)
                ->add('aidSteps', EntityCheckboxAbsoluteType::class, $aidStepsParams)
                ->add('aidDestinations', EntityCheckboxAbsoluteType::class, $aidDestinationsParams)
                ->add('isCharged', ChoiceType::class, $isChargedParams)
                ->add('europeanAid', ChoiceType::class, $europeanAidParams)
                ->add('isCallForProject', CheckboxType::class, $isCallForProjectParams)
            ;
        }

        foreach ($options['removes'] as $remove) {
            if ($builder->has($remove)) {
                $builder->remove($remove);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'forceOrganizationType' => false,
            'forcePerimeter' => false,
            'dontUseUserPerimeter' => false,
            'forceKeyword' => false,
            'forceCategorySearch' => false,
            'forceAidTypes' => false,
            'forceBackers' => false,
            'forceApplyBefore' => false,
            'forcePrograms' => false,
            'forceAidSteps' => false,
            'forceAidDestinations' => false,
            'forceIsCharged' => false,
            'forceEuropeanAid' => false,
            'forceIsCallForProject' => false,

            'forceNewIntegration' => false,
            'extended' => false,
            'removes' => [],
            
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
