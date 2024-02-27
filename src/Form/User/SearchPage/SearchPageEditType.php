<?php

namespace App\Form\User\SearchPage;

use App\Entity\Aid\Aid;
use App\Entity\Search\SearchPage;
use App\Form\Type\AidAutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Count;

class SearchPageEditType extends AbstractType
{
    public function __construct(
        private RouterInterface $routerInterface)
    {
        
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'Contenu de la page :',
                'help' => 'Description complète de la page. Sera affichée au dessus des résultats.',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('moreContent', TextareaType::class, [
                'required' => false,
                'label' => 'Contenu additionnel :',
                'help' => 'Contenu caché, révélé au clic sur le bouton « Voir plus ».',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])

            ->add('highlightedAids', AidAutocompleteType::class, [
                'label' => 'Mettre en avant des aides',
                'help' => 'Tapez le nom exact de l\'aide pour la sélectionner. Il est possible de mettre jusqu’à 9 aides en avant. Les aides mises en avant s’affichent en haut des résultats du portail, et n’ont pas de mise en forme particulière.',
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
                'entry_options' => array(
                    'label' => false
                ),
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'by_reference' => false
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
