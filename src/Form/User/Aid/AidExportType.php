<?php

namespace App\Form\User\Aid;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AidExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('format', ChoiceType::class, [
                'required' => true,
                'label' => 'Veuillez sélectionner le format d’export : ',
                'help' => 'L\'export PDF est envoyé par email.',
                'choices' => [
                    'Fichier CSV' => 'csv',
                    'Tableur Excel' => 'xlsx',
                    'Document PDF' => 'pdf'
                ],
                'expanded' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez choisir un format.',
                    ]),
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
