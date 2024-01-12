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
                'label' => 'Périmètres à additionner',
                'attr' => [
                    'data-controller' => 'custom-autocomplete'
                ],
                'help' => 'Sélectionnez une liste de périmètres à combiner',
                'multiple' => true
            ])
            ->add('perimetersToRemove', PerimeterAutocompleteType::class, [
                'required' => false,
                'label' => 'Périmètres à soustraire',
                'attr' => [
                    'data-controller' => 'custom-autocomplete'
                ],
                'help' => 'Ces périmètres seront enlevés du périmètre combiné.',
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
