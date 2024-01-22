<?php

namespace App\Form\User\Project;

use App\Service\File\FileService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('format', ChoiceType::class, [
                'required' => true,
                'label' => 'Veuillez sélectionner le format d’export :',
                'choices' => [
                    'Fichier CSV ' => FileService::FORMAT_CSV,
                    'Tableur Excel ' => FileService::FORMAT_XLSX,
                    'Document PDF ' => FileService::FORMAT_PDF
                ],
                'expanded' => true,
            ])
            ->add('idProject', HiddenType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
