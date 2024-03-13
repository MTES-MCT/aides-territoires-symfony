<?php

namespace App\Form\Backer;

use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerUser;
use App\Entity\User\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BackerUserAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('administrator', ChoiceType::class, [
                'label' => 'Est administrateur ?',
                'required' => true,
                'choices' => [
                    'Non' => false,
                    'Oui' => true,
                ],
            ])
            ->add('editor', ChoiceType::class, [
                'label' => 'Est éditeur ?',
                'required' => true,
                'choices' => [
                    'Non' => false,
                    'Oui' => true,
                ],
            ])
            ->add('email', TextType::class, [
                'required' => true,
                'mapped' => false,
                'label' => 'Email de l\'utilisateur à ajouter',
                'help' => 'L\'utilisateur doit être inscrit sur la plateforme Aides-Territoires',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BackerUser::class,
        ]);
    }
}
