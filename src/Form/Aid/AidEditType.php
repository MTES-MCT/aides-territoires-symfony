<?php

namespace App\Form\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Form\Type\EntityCheckboxAbsoluteType;
use App\Form\Type\EntityCheckboxGroupAbsoluteType;
use App\Form\Type\EntityGroupedType;
use App\Form\Type\PerimeterAutocompleteType;
use App\Service\User\UserService;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType as TypeIntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count as ConstraintsCount;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Url;

class AidEditType extends AbstractType
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected UserService $userService
    )
    {
        
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $aid = $options['data'] ?? null;
        $isDraft = ($aid instanceof Aid && $aid->getStatus() === Aid::STATUS_DRAFT) || ($aid instanceof Aid && !$aid->getId());

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
        
        $aid = $options['data'] ?? null;
        $financers = [];
        $instructors = [];
        if ($aid) {
            foreach ($aid->getAidFinancers() as $aidFinancer) {
                $financers[] = $aidFinancer->getBacker();
            }
            foreach ($aid->getAidInstructors() as $aidInstructor) {
                $instructors[] = $aidInstructor->getBacker();
            }
        }

        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Nom',
                'help' => 'Le titre doit commencer par un verbe à l’infinitif pour que l’objectif de l’aide soit explicite vis-à-vis de ses bénéficiaires.',
                'attr' => [
                    'maxlength' => 180,
                ],
                'constraints' => [
                    new Length(null, null, 180)
                ],
            ])
            ->add('nameInitial', TextType::class, [
                'required' => false,
                'label' => 'Nom initial',
                'help' => 'Comment cette aide s’intitule-t-elle au sein de votre structure ? Exemple : AAP Mob’Biodiv',
            ])
            ->add('organization', EntityType::class, [
                'required' => true,
                'label' => 'L\'organisation pour laquelle vous publiez cette aide',
                'class' => Organization::class,
                'choice_label' => 'name',
                'query_builder' => function(EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('o')
                    ->innerJoin('o.beneficiairies', 'beneficiairies')
                    ->andWhere('beneficiairies = :user')
                    ->setParameter('user', $this->userService->getUserLogged())
                    ->orderBy('o.name', 'ASC')
                    ;
                },
            ])
            ->add('programs', EntityCheckboxAbsoluteType::class, [
                'required' => false,
                'label' => 'Programmes d\'aides',
                'placeholder' => 'Tous les programmes',
                'class' => Program::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true
            ])
            ->add('financers', EntityType::class, [
                'required' => $isDraft ? false : true,
                'mapped' => false,
                'label' => 'Porteurs d\'aides',
                'help' => 'Saisissez quelques caractères et sélectionnez une valeur parmi les suggestions.',
                'class' => Backer::class,
                'choice_label' => 'name',
                'attr' => [
                    'placeholder' => 'Sélectionnez le ou les porteurs',
                ],
                'autocomplete' => true,
                'multiple' => true,
                'query_builder' => function(EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('b')->orderBy('b.name', 'ASC');
                },
                'data' => $financers
            ])
            ->add('financerSuggestion', TextType::class, [
                'required' => false,
                'label' => 'Suggérer un nouveau porteur',
                'help' => 'Suggérez un porteur si vous ne trouvez pas votre choix dans la liste principale.',
            ])
            ->add('instructors', EntityType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Instructeurs',
                'help' => 'Saisissez quelques caractères et sélectionnez une valeur parmi les suggestions.',
                'class' => Backer::class,
                'choice_label' => 'name',
                'attr' => [
                    'placeholder' => 'Sélectionnez le ou les instructeurs parmis la liste',
                ],
                'autocomplete' => true,
                'multiple' => true,
                'query_builder' => function(EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('b')->orderBy('b.name', 'ASC');
                },
                'data' => $instructors
            ])
            ->add('instructorSuggestion', TextType::class, [
                'required' => false,
                'label' => 'Suggérer un nouvel instructeur',
                'help' => 'Suggérez un instructeur si vous ne trouvez pas votre choix dans la liste principale.',
            ])
            ->add('aidAudiences', EntityGroupedType::class, [
                'required' => $isDraft ? false : true,
                'label' => 'Bénéficiaires de l’aide',
                'class' => OrganizationType::class,
                'choice_label' => 'name',
                'group_by' => function(OrganizationType $organizationType) {
                    return $organizationType->getOrganizationTypeGroup()->getName();
                },
                'expanded' => true,
                'multiple' => true,
                // 'constraints' => [
                //     new ConstraintsCount(null, 1)
                // ]
            ])
            ->add('aidTypes', EntityGroupedType::class, [
                'required' => $isDraft ? false : true,
                'label' => 'Types daide',
                'help' => 'Précisez le ou les types de l’aide.',
                'class' => AidType::class,
                'choice_label' => 'name',
                'group_by' => function(AidType $aidType) {
                    return $aidType->getAidTypeGroup()->getName();
                },
                'expanded' => true,
                'multiple' => true,
                // 'constraints' => [
                //     new ConstraintsCount(null, 1)
                // ]
            ])
            ->add('subventionRateMin', TypeIntegerType::class, [
                'required' => false,
                'label' => 'Taux de subvention, min. et max. (en %, nombre entier)',
                'help' => 'Si le taux est fixe, remplissez uniquement le taux max.',
                'attr' => [
                    'placeholder' => 'Taux de subvention min'
                ],
                'constraints' => [
                    new PositiveOrZero()
                ]
            ])
            ->add('subventionRateMax', TypeIntegerType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Taux de subvention max'
                ],
                'constraints' => [
                    new PositiveOrZero()
                ]
            ])
            ->add('subventionComment', TextType::class, [
                'required' => false,
                'label' => 'Taux de subvention (commentaire optionnel)'
            ])
            ->add('loanAmount', TypeIntegerType::class, [
                'required' => false,
                'label' => 'Montant du prêt maximum',
                'attr' => [
                    'min' => 0
                ],
                'constraints' => [
                    new PositiveOrZero()
                ],
            ])
            ->add('recoverableAdvanceAmount', TypeIntegerType::class, [
                'required' => false,
                'label' => 'Montant de l’avance récupérable',
                'attr' => [
                    'min' => 0
                ],
                'constraints' => [
                    new PositiveOrZero()
                ]
            ])
            ->add('otherFinancialAidComment', TextType::class, [
                'required' => false,
                'label' => 'Autre aide financière (commentaire optionnel)',
            ])

            ->add('isCharged', CheckboxType::class, [
                'required' => false,
                'label' => 'Aide Payante',
                'help' => 'Ne pas cocher pour les aides sous adhésion et ajouter la mention « *sous adhésion » dans les critères d’éligibilité.'
            ])
            ->add('isCallForProject', CheckboxType::class, [
                'required' => false,
                'label' => 'Appel à projet / Manifestation d’intérêt'
            ])
            ->add('description', TextareaType::class, [
                'required' => $isDraft ? false : true,
                'label' => 'Description complète de l’aide et de ses objectif',
                'attr' => [
                    'placeholder' => 'Si vous avez un descriptif, n’hésitez pas à le copier ici.
                    Essayez de compléter le descriptif avec le maximum d’informations.
                    Si l’on vous contacte régulièrement pour vous demander les mêmes "
                    informations, essayez de donner des éléments de réponses dans cet espace.',
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('projectExamples', TextareaType::class, [
                'required' => false,
                'label' => 'Exemples d’applications ou de projets réalisés grâce à cette aide',
                'help' => 'Afin d’aider les territoires à mieux comprendre votre aide, donnez ici quelques exemples concrets de projets réalisables ou réalisés.',
                'attr' => [
                    'placeholder' => 'Médiathèque, skatepark, accompagner des enfants en classe de neige, financer une usine de traitement des déchets, etc.',
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('categories', EntityCheckboxGroupAbsoluteType::class, [
                'required' => $isDraft ? false : true,
                'label' => 'Thématiques de l\'aide',
                'placeholder' => 'Toutes les sous-thématiques',
                'help' => 'Sélectionnez la ou les thématiques associées à votre aide. N’hésitez pas à en choisir plusieurs.',
                'class' => Category::class,
                'choice_label' => 'name',
                'group_by' => function(Category $category) {
                    return $category->getCategoryTheme()->getName();
                },
                'multiple' => true,
                'expanded' => true
            ])
            ->add('aidRecurrence', EntityType::class, [
                'required' => $isDraft ? false : true,
                'label' => 'Récurrence',
                'help' => 'L’aide est-elle ponctuelle, permanente, ou récurrent',
                'placeholder' => '---------',
                'class' => AidRecurrence::class,
                'choice_label' => 'name'
            ])
            ->add('dateStart', DateType::class, [
                'required' => false,
                'label' => 'Date d’ouverture',
                'help' => 'À quelle date l’aide est-elle ouverte aux candidatures ?',
                'widget' => 'single_text',
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('dateSubmissionDeadline', DateType::class, [
                'required' => false,
                'label' => 'Date de clôture',
                'help' => 'Quelle est la date de clôture de dépôt des dossiers ?',
                'widget' => 'single_text',
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('eligibility', TextareaType::class, [
                'required' => false,
                'label' => 'Conditions d’éligibilité',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('aidSteps', EntityType::class, [
                'required' => $isDraft ? false : true,
                'label' => 'État d’avancement du projet pour bénéficier du dispositif',
                'class' => AidStep::class,
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
                'label_attr' => [
                    'class' => 'fr-fieldset__legend'
                ]
            ])
            ->add('aidDestinations', EntityType::class, [
                'required' => false,
                'label' => 'Types de dépenses / actions couvertes',
                'help' => 'Obligatoire pour les aides financières',
                'class' => AidDestination::class,
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
                'label_attr' => [
                    'class' => 'fr-fieldset__legend'
                ]
            ])
            ->add('perimeter', PerimeterAutocompleteType::class, [
                'required' => $isDraft ? false : true,
                'label' => 'Zone géographique couverte par l’aide',
                'help' => 'La zone géographique sur laquelle l\'aide est disponible.<br />
                Exemples de zones valides :
                <ul>
                    <li>France</li>
                    <li>Bretagne (Région)</li>
                    <li>Métropole du Grand Paris (EPCI)</li>
                    <li>Outre-mer</li>
                    <li>Wallis et Futuna</li>
                    <li>Massif Central</li>
                </ul>
                ',
                'help_html' => true,
                'placeholder' => 'Tapez les premiers caractères',
                'class' => Perimeter::class,
            ])
            ->add('perimeterSuggestion', TextType::class, [
                'required' => false,
                'label' => 'Vous ne trouvez pas de zone géographique appropriée ?',
                'help' => 'Si vous ne trouvez pas de zone géographique suffisamment précise dans la liste existante, spécifiez « France » et décrivez brièvement ici le périmètre souhaité.',
            ])
            ->add('originUrl', TextType::class, [
                'required' => $isDraft ? false : true,
                'label' => 'Lien vers plus d’information (url d’origine, site du porteur d’aides)',
                'constraints' => [
                    new Url()
                ],
            ])
            ->add('applicationUrl', TextType::class, [
                'required' => false,
                'label' => 'Lien vers une démarche en ligne pour candidater',
                'constraints' => [
                    new Url()
                ],
            ])
            ->add('contact', TextareaType::class, [
                'required' => $isDraft ? false : true,
                'label' => 'Contact pour candidater',
                'help' => 'N’hésitez pas à ajouter plusieurs contacts',
                'attr' => [
                    'placeholder' => 'Nom, prénom, e-mail, téléphone, commentaires…',
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])

            ->addEventListener(
                FormEvents::SUBMIT,
                [$this, 'onSubmit']
            )
        ;

        // gestion du statut
        $statusParams = [
            'required' => true,
            'label' => false,
            'choices' => [
                'Brouillon' => Aid::STATUS_DRAFT,
                'En revue' => Aid::STATUS_REVIEWABLE
            ],
            'attr' => [
                'autocomplete' => 'off'
            ]
        ];
        if ($options['allowStatusPublished']) {
            $statusParams['choices']['Publiée'] = Aid::STATUS_PUBLISHED;
        }
        $builder
            ->add('status', ChoiceType::class, $statusParams)
        ;

        if ($options['data'] instanceof Aid) {
            if ($options['data']->isLocal()) {
                $builder
                ->add('localCharacteristics', TextareaType::class, [
                    'required' => false,
                    'label' => 'Spécificités locales',
                    'help' => 'Décrivez les spécificités de cette aide locale.',
                    'attr' => [
                        'class' => 'trumbowyg'
                    ],
                    'sanitize_html' => true,
                ]);
            }
        }
    }

    public function onSubmit(FormEvent $event): void
    {
        // si aide permanente, on force date ouverture et cloture à null
        if ($event->getForm()->has('aidRecurrence') && $event->getForm()->get('aidRecurrence')->getData() && $event->getForm()->get('aidRecurrence')->getData()->getSlug() == AidRecurrence::SLUG_ONGOING) {
            $event->getData()->setDateStart(null);
            $event->getData()->setDateSubmissionDeadline(null);
        }
        
        // vérifications subventions
        $subventionRateMin = $event->getForm()->has('subventionRateMin') ? $event->getForm()->get('subventionRateMin')->getData() : null;
        $subventionRateMax = $event->getForm()->has('subventionRateMax') ? $event->getForm()->get('subventionRateMax')->getData() : null;
        if ($subventionRateMin && $subventionRateMax && $subventionRateMin > $subventionRateMax) {
            $event->getForm()->get('subventionRateMin')->addError(new FormError('Doit être inférieur au taux de subvention max'));
            $event->getForm()->get('subventionRateMax')->addError(new FormError('Doit être supérieur au taux de subvention min'));
        }

        $aidTypes = $event->getForm()->has('aidTypes') ? $event->getForm()->get('aidTypes')->getData() : null;
        $status = $event->getForm()->get('status')->getData();

        $fieldsToSwitch = [
            'financers',
            'aidAudiences',
            'aidTypes',
            'description',
            'categories',
            'aidRecurrence',
            'aidSteps',
            'perimeter',
            'originUrl',
            'contact',
        ];
        $fieldsOneMin = [
            'aidAudiences',
            'aidTypes',
            'aidSteps'
        ];

        if ($status !== Aid::STATUS_DRAFT) {
            foreach ($fieldsToSwitch as $field) {
                if ($event->getForm()->has($field)) {
                    if (!$event->getForm()->get($field)->getData()) {
                        $event->getForm()->get($field)->addError(new FormError('Ce champ est obligatoire si votre aide n\'est pas en brouillon'));
                    }
                }
            }
            foreach ($fieldsOneMin as $field) {
                if ($event->getForm()->has($field)) {
                    if (count($event->getForm()->get($field)->getData()) < 1) {
                        $event->getForm()->get($field)->addError(new FormError('Ce champ est obligatoire si votre aide n\'est pas en brouillon'));
                    }
                }
            }
        }

        if ($aidTypes && $status !== Aid::STATUS_DRAFT) {
            /** @var AidType $aidType */
            $typeError = false;
            foreach ($aidTypes as $aidType) {
                // c'est une aide financière, Types de dépenses / actions couvertes est obligatoire
                if ($aidType->getAidTypeGroup()->getSlug() == AidTypeGroup::SLUG_FINANCIAL) {
                    if ($event->getForm()->has('aidDestinations') && !count($event->getForm()->get('aidDestinations')->getData()) && !$typeError) {
                        $typeError = true; // pour ne pas ajouter plusieurs fois l'erreur
                        $event->getForm()->get('aidDestinations')->addError(new FormError('Veuillez compléter le champ types de dépenses / actions couvertes '));
                    }
                }
            }

            // si récurrence "Ponctuelle" ou "Récurrent" => date de clôture obligatoire
            if ($event->getForm()->has('aidRecurrence') && $event->getForm()->get('aidRecurrence')->getData() && in_array($event->getForm()->get('aidRecurrence')->getData()->getSlug(), [AidRecurrence::SLUG_ONEOFF, AidRecurrence::SLUG_RECURRING])) {
                if ($event->getForm()->has('dateSubmissionDeadline') && !$event->getForm()->get('dateSubmissionDeadline')->getData()) {
                    $event->getForm()->get('dateSubmissionDeadline')->addError(new FormError('Ce champ est obligatoire pour les aides ponctuelles ou récurrentes'));
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Aid::class,
            'allowStatusPublished' => false,
        ]);
    }
}
