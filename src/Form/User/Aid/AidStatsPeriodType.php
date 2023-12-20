<?php

namespace App\Form\User\Aid;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AidStatsPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateMin', DateType::class, [
                'required' => true,
                'label' => 'Date de début',
                'widget' => 'single_text'
            ])
            ->add('dateMax', DateType::class, [
                'required' => true,
                'label' => 'Date de fin',
                'widget' => 'single_text'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
