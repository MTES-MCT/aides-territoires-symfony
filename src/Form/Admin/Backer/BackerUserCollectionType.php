<?php

namespace App\Form\Admin\Backer;

use App\Entity\Backer\BackerUser;
use App\Entity\User\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BackerUserCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('administrator', ChoiceType::class, [
                'label' => 'Est administrateur ?',
                'help' => 'L\'administrateur à également les droits de l\'éditeur. Il s\'occuppe de la gestion des utilisateurs.',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
                'expanded' => true,
            ])
            ->add('editor', ChoiceType::class, [
                'label' => 'Est éditeur ?',
                'help' => 'Peu modifier la fiche du porteur.',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
                'expanded' => true,
            ])
            ->add('user', EntityType::class, [
                'label' => 'Utilisateur',
                'class' => User::class,
                'choice_label' => 'email',
                'autocomplete' => true
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
