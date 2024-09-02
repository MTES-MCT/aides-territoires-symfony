<?php

namespace App\Form\Admin\Reference;

use App\Entity\Reference\ProjectReferenceMissing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class ProjectReferenceMissingCreateType extends AbstractType
{
    public function __construct(
        private RouterInterface $routerInterface
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('name', TextType::class, [
            'label' => 'Nom du projet référent manquant',
            'autocomplete' => true,
            'autocomplete_url' => $this->routerInterface->generate('app_admin_project_reference_missing_ajax_ux_autocomplete'),
            'required' => false,
            'attr' => [
                'data-controller' => 'custom-autocomplete',
                'placeholder' => 'Saisir un nom'
            ],
            'tom_select_options' => [
                'create' => true,
                'createOnBlur' => true,
                'maxItems' => 1,
                'selectOnTab' => true,
                'closeAfterSelect' => true,
                'sanitize_html' => true,
                'delimiter' => '$%§'
            ],
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectReferenceMissing::class,
        ]);
    }
}
