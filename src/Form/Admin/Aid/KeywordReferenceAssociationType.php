<?php

namespace App\Form\Admin\Aid;

use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Form\Type\EntityCheckboxAbsoluteType;
use App\Form\Type\EntityCheckboxGroupAbsoluteType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KeywordReferenceAssociationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('keywordReferences', EntityType::class, [
                'class' => KeywordReference::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir',
                'multiple' => true,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Rechercher un mot-clé',
                ],
            ])
            ->add('batchActionName', HiddenType::class)
            ->add('entityFqcn', HiddenType::class)
            ->add('batchActionUrl', HiddenType::class)
            ->add('batchActionCsrfToken', HiddenType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'allow_extra_fields' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
