<?php

namespace App\Form\User\Aid;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
class AidStatsPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateMin', DateType::class, [
                'required' => true,
                'label' => 'Date de début',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner une date de début.',
                    ]),
                    new Assert\Date([
                        'message' => 'La date de début n\'est pas valide.',
                    ]),
                ],
            ])
            ->add('dateMax', DateType::class, [
                'required' => true,
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner une date de fin.',
                    ]),
                    new Assert\Date([
                        'message' => 'La date de fin n\'est pas valide.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
