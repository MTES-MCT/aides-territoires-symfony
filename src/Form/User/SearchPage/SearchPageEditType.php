<?php

namespace App\Form\User\SearchPage;

use App\Entity\Aid\Aid;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Form\Type\AidAutocompleteType;
use App\Form\Type\EntityCheckboxAbsoluteType;
use App\Form\Type\EntityCheckboxGroupAbsoluteType;
use App\Service\User\UserService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints as Assert;

class SearchPageEditType extends AbstractType
{
    public function __construct(
        private UserService $userService
    ) {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $this->userService->getUserLogged();
        $searchPage = $options['data'] ?? null;

        if ($searchPage instanceof SearchPage && $searchPage->getAdministrator() == $currentUser) {
            $builder
                ->add(
                    'editors',
                    EntityType::class,
                    [
                    'required' => true,
                    'label' => 'Éditeur(s) de la page',
                    'help' => 'Vous pouvez ajouter plusieurs éditeurs à la page. '
                                    . ' Les éditeurs peuvent modifier le contenu de la page, '
                                    . 'ils doivent faire parti de vos structures.',
                    'class' => User::class,
                    'choice_label' => 'email',
                    'multiple' => true,
                    'expanded' => true,
                    'query_builder' => function (EntityRepository $er) use ($currentUser) {
                        return $er->createQueryBuilder('u')
                            ->innerJoin('u.organizations', 'o')
                            ->andWhere('o IN (:organizations)')
                            ->setParameter('organizations', $currentUser->getOrganizations())
                            ->andWhere('u != :currentUser')
                            ->setParameter('currentUser', $currentUser)
                            ->orderBy('u.email', 'ASC');
                    },
                    ]
                );
        }

        $builder
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'Contenu de la page :',
                'help' => 'Description complète de la page. Sera affichée au dessus des résultats.',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10,
                    'autocomplete' => 'off'
                ],
                'sanitize_html' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir la description.',
                    ]),
                ]
            ])
            ->add('moreContent', TextareaType::class, [
                'required' => false,
                'label' => 'Contenu additionnel :',
                'help' => 'Contenu caché, révélé au clic sur le bouton « Voir plus ».',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10,
                    'autocomplete' => 'off'
                ],
                'sanitize_html' => true,
            ])

            ->add('highlightedAids', AidAutocompleteType::class, [
                'required' => false,
                'label' => 'Mettre en avant des aides',
                'help' => 'Tapez le nom exact de l\'aide pour la sélectionner. '
                            . 'Il est possible de mettre jusqu’à 9 aides en avant. '
                            . 'Les aides mises en avant s’affichent en haut des résultats du portail, '
                            . 'et n’ont pas de mise en forme particulière.',
                'class' => Aid::class,
                'choice_label' => 'name',
                'multiple' => true,
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => '$%§'
                ],
                'constraints' => [
                    new Count(max: 9)
                ]
            ])

            ->add('excludedAids', AidAutocompleteType::class, [
                'required' => false,
                'label' => 'Exclure des aides des résultats',
                'help' => 'Tapez le nom exact de l\'aide pour la sélectionner',
                'class' => Aid::class,
                'choice_label' => 'name',
                'multiple' => true,
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => '$%§'
                ],
            ])

            ->add('pages', CollectionType::class, [
                'required' => true,
                'entry_type' => SearchPageOngletType::class,
                'entry_options' => [
                    'label' => false
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'by_reference' => false
            ])

            ->add('showAudienceField', ChoiceType::class, [
                'required' => true,
                'label' => 'Afficher le champ "structures"',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
            ])
            ->add('organizationTypes', EntityCheckboxAbsoluteType::class, [
                'required' => false,
                'label' => 'Restreindre à ces types de structures',
                'class' => OrganizationType::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'autocomplete' => true
            ])

            ->add('showPerimeterField', ChoiceType::class, [
                'required' => true,
                'label' => 'Afficher le champ "territoire"',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
            ])

            ->add('showTextField', ChoiceType::class, [
                'required' => true,
                'label' => 'Afficher le champ "mot clé"',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
            ])

            ->add('showCategoriesField', ChoiceType::class, [
                'required' => true,
                'label' => 'Afficher le champ "thématiques"',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
            ])

            ->add('categories', EntityCheckboxGroupAbsoluteType::class, [
                'required' => false,
                'label' => 'Restreindre à ces types de thématiques',
                'class' => Category::class,
                'choice_label' => 'name',
                'group_by' => function (Category $category) {
                    return $category->getCategoryTheme()->getName();
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->innerJoin('c.categoryTheme', 'categoryTheme')
                        ->orderBy('categoryTheme.name', 'ASC')
                        ->addOrderBy('c.name', 'ASC')
                    ;
                },
                'multiple' => true,
                'expanded' => true
            ])

            ->add('showAidTypeField', ChoiceType::class, [
                'required' => true,
                'label' => 'Afficher le champ "nature de l\'aide"',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
            ])

            ->add('showBackersField', ChoiceType::class, [
                'required' => true,
                'label' => 'Afficher le champ "porteurs"',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
            ])

            ->add('showMobilizationStepField', ChoiceType::class, [
                'required' => true,
                'label' => 'Afficher le champ "avancement du projet"',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchPage::class,
        ]);
    }
}
