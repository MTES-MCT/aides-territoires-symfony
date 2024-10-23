<?php

namespace App\Form\Admin\Perimeter;

use App\Form\Type\PerimeterAutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CombineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('perimetersToAdd', PerimeterAutocompleteType::class, [
                'required' => false,
                'label' => 'Périmètres à additionner récursif',
                'attr' => [
                    'data-controller' => 'custom-autocomplete'
                ],
                'help' => 'Sélectionnez une liste de périmètres à combiner. '
                    . 'Pour chaque périmètre ses enfants et ses parents seront ajoutés. '
                    . 'Ex: En ajout "CC Alpes d’Azur" à "Gal Alpes et Azur", '
                    . 'on ajoute aussi les communes de "CC Alpes d’Azur" à "Gal Alpes et Azur" '
                    . 'et on ajoute "Gal Alpes et Azur" aux parents de "CC Alpes d’Azur" (Région, Département, etc.)',
                'multiple' => true
            ])
            ->add('perimetersToAddStrict', PerimeterAutocompleteType::class, [
                'required' => false,
                'label' => 'Périmètres à additionner stricte',
                'attr' => [
                    'data-controller' => 'custom-autocomplete'
                ],
                'help' => 'Sélectionnez une liste de périmètres à combiner. '
                    . 'Seul les périmètre sélectionner seront ajoutés (les enfants et parents seront ignorés). '
                    . 'C\'est l\'ancien comportement.',
                'multiple' => true
            ])

            ->add('perimetersFromRemove', PerimeterAutocompleteType::class, [
                'required' => false,
                'label' => 'Périmètres enfants à soustraire',
                'attr' => [
                    'data-controller' => 'custom-autocomplete'
                ],
                'help' => 'Ces périmètres seront enlevés des enfants du périmètre. '
                    . 'Ex: Retiré "Alpes-Maritîmes" des parents de "Gal Alpes et Azur". '
                    . 'Les enfants et parents de "Alpes-Maritîmes" ne seront pas traités.',
                'multiple' => true
            ])
            ->add('perimetersToRemove', PerimeterAutocompleteType::class, [
                'required' => false,
                'label' => 'Périmètres parents à soustraire',
                'attr' => [
                    'data-controller' => 'custom-autocomplete'
                ],
                'help' => 'Ces périmètres seront enlevés des parents du périmètre. '
                    . 'Ex: Retirer "CC Alpes d’Azur" à "Gal Alpes et Azur". '
                    . 'Les enfants et parents de "CC Alpes d’Azur" ne seront pas traités.',
                'multiple' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
