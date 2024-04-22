<?php

namespace App\Form\Organization;

use App\Entity\Organization\OrganizationAccess;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationAccessEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('administrator', CheckboxType::class, [
                'required' => false,
                'label' => ' ',
                'attr' => [
                    // 'readonly' => 'readonly'    
                ]
            ])
            ->add('editAid', CheckboxType::class, [
                'required' => false,
                'label' => ' '
            ])
            ->add('editPortal', CheckboxType::class, [
                'required' => false,
                'label' => ' '
            ])
            ->add('editBacker', CheckboxType::class, [
                'required' => false,
                'label' => ' '
            ])
            ->add('editProject', CheckboxType::class, [
                'required' => false,
                'label' => ' '
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrganizationAccess::class,
        ]);
    }
}
