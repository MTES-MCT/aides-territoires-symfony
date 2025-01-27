<?php

namespace App\Form\Admin\Filter\Backer;

use App\Entity\Perimeter\Perimeter;
use App\Form\Type\PerimeterAutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

#[AsEntityAutocompleteField]
class BackerPerimeterFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comparison', ChoiceType::class, [
                'choices' => [
                    'couvre' => 'eq',
                    'ne couvre pas' => 'neq',
                    'est strictement' => 'eq_strict',
                ],
                'required' => true,
            ])
            ->add('value', PerimeterAutocompleteType::class, [
                'class' => Perimeter::class,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Choix périmètre'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
