<?php

namespace App\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_username', EmailType::class, [
                'required' => true,
                'label' => 'Votre adresse e-mail',
                'attr' => [
                    'autofocus' => true,
                    'maxlength' => 254,
                    'autocomplete' => 'email'
                ],
                'help' => 'Par exemple : prenom.nom@domaine.fr',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre email.',
                    ]),
                    new Assert\Email([
                        'message' => 'L\'email "{{ value }}" n\'est pas une adresse email valide.',
                    ]),
                ],
            ])
            ->add('_password', PasswordType::class, [
                'required' => true,
                'label' => 'Votre mot de passe',
                'toggle' => true,
                'hidden_label' => 'Cacher',
                'visible_label' => 'Montrer',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre mot de passe.',
                    ]),
                ],
            ])
            ->add('_remember_me', CheckboxType::class, [
                'required' => false,
                'label' => 'Se souvenir de moi'
            ])
            ->add('_target_path', HiddenType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // enable/disable CSRF protection for this form
            'csrf_protection' => true,
            // the name of the hidden HTML field that stores the token
            'csrf_field_name' => '_csrf_token',
            // an arbitrary string used to generate the value of the token
            // using a different string for each form improves its security
            'csrf_token_id'   => 'authenticate',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return ''; // pour ne pas avoir le soucis des name[]
    }
}
