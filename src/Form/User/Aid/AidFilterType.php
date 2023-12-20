<?php

namespace App\Form\User\Aid;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AidFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('state', ChoiceType::class, [
                'required' => false,
                'label' => 'Échéance :',
                'placeholder' => 'Sélectionnez une option',
                'choices' => [
                    'Ouverte' => 'open',
                    'Expire bientôt' => 'deadline',
                    'Expirée' => 'expired'
                ]
            ])
            ->add('statusDisplay', ChoiceType::class, [
                'required' => false,
                'label' => 'Affichage :',
                'placeholder' => 'Sélectionnez une option',
                'choices' => [
                    'Non affichée' => 'hidden',
                    'Affichées' => 'live'
                ]
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
