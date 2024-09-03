<?php

namespace App\Form\User;

use App\Entity\User\User;
use App\Service\User\UserService;
use App\Validator\PasswordProfil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserProfilType extends AbstractType
{
    public function __construct(
        protected UserService $userService,
        protected UserPasswordHasherInterface $userPasswordHasherInterface
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Votre prénom :',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre prénom.',
                    ]),
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Votre nom :',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre nom.',
                    ]),
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre email :',
                'required' => true,
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
            ->add('beneficiaryFunction', ChoiceType::class, [
                'choices' => [
                    'Maire' =>  'mayor',
                    'Adjoint au maire' => 'deputy_mayor',
                    'Conseiller municipal' => 'municipal_councilor',
                    'Élu' => 'elected',
                    'Secrétaire de mairie' => 'town_clerk',
                    'Agent territorial' => 'agent',
                    'Autre' => 'other',
                ],
                'placeholder' => 'Faites votre choix',
                'label' => 'Vous êtes :',
                'required'  => false,

            ])
            ->add('beneficiaryRole', TextType::class, [
                'label' => 'Votre fonction :',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre fonction.',
                    ]),
                ]
            ])
            ->add('isBeneficiary', CheckboxType::class, ['label' => 'Trouver des aides', 'required' => false])
            ->add('isContributor', CheckboxType::class, ['label' => 'Publier des aides', 'required' => false])
            ->add(
                'oldPassword',
                PasswordType::class,
                [
                    'label' => 'Entrez votre mot de passe actuel',
                    'mapped' => false,
                    'required' => false,
                    'attr' => ['placeholder' => 'A remplir seulement en cas de changement de mot de passe']
                ]
            )
            ->add('newPassword', RepeatedType::class, [
                'mapped' => false,
                'required' => false,
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'options' => ['attr' => ['class' => 'password-field']],
                'first_options'  => [
                    'label' => 'Choisissez un nouveau mot de passe',
                    'help' => '<ul>
                    <li>Votre mot de passe ne peut pas trop ressembler à vos autres informations personnelles</li>
                    <li>Votre mot de passe doit contenir au minimum 9 caractères</li>
                    <li>Votre mot de passe ne peut pas être un mot de passe couramment utilisé</li>
                    <li>Votre mot de passe ne peut pas être entièrement numérique</li>
                    </ul>',
                    'help_html' => true,
                ],
                'second_options' => ['label' => 'Saisissez à nouveau le nouveau mot de passe'],
                'constraints' => [
                    new PasswordProfil(),
                ],
            ])

            ->addEventListener(
                FormEvents::SUBMIT,
                [$this, 'onSubmit']
            )
        ;
    }

    public function onSubmit(FormEvent $event): void
    {
        if ($event->getForm()->has('oldPassword') && $event->getForm()->get('oldPassword')->getData()) {
            $user = $this->userService->getUserLogged();
            if (!$this->userPasswordHasherInterface->isPasswordValid($user, $event->getForm()->get('oldPassword')->getData())) {
                $event->getForm()->get('oldPassword')->addError(new FormError('Le mot de passe actuel est incorrect.'));
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // enable/disable CSRF protection for this form
            'csrf_protection' => true,
            // the name of the hidden HTML field that stores the token
            'csrf_field_name' => '_csrf_token',
            // an arbitrary string used to generate the value of the token
            // using a different string for each form improves its security
            'csrf_token_id'   => 'authenticate',
        ]);
    }
}
