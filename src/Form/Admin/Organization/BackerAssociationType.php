<?php

namespace App\Form\Admin\Organization;

use App\Entity\Backer\Backer;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BackerAssociationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('backer', EntityType::class, [
                'class' => Backer::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir un porteur',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('b')
                        ->orderBy('b.name', 'ASC');
                },
                'autocomplete' => true
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
