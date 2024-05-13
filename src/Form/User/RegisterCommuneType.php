<?php

namespace App\Form\User;

use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Form\Type\PerimeterCityAutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterCommuneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        // choix beneficiaryFunction
        $choicesBeneficiaryFunction = [];
        foreach (User::FUNCTION_TYPES as $functionType) {
            $choicesBeneficiaryFunction[$functionType['name']] = $functionType['slug'];
        }

        $builder
            ->add('beneficiaryFunction', ChoiceType::class, [
                'required' => false,
                'label' => 'Votre fonction', 
                'placeholder' => 'Sélectionnez votre fonction',
                'choices' => $choicesBeneficiaryFunction,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('perimeter', PerimeterCityAutocompleteType::class, [
                'required' => true,
                'label' => 'Votre commune',
                'placeholder' => 'Tapez les premiers caractères',
                'class' => Perimeter::class,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez choisir votre commune.',
                    ]),
                ]
            ])
            ->add('firstname', TextType::class, [
                'required' => true,
                'label' => 'Votre prénom',
                'attr' => [
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre prénom.',
                    ]),
                ]
            ])
            ->add('lastname', TextType::class, [
                'required' => true,
                'label' => 'Votre nom',
                'attr' => [
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre nom.',
                    ]),
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'Votre adresse e-mail',
                'help' => 'Par exemple : prenom.nom@domaine.fr<br />Nous enverrons un e-mail de confirmation à cette adresse avant de valider le compte.',
                'help_html' => true,
                'attr' => [
                    'placeholder' => 'Merci de bien vérifier l\'adresse saisie',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre email.',
                    ]),
                    new Assert\Email([
                        'message' => 'L\'email "{{ value }}" n\'est pas une adresse email valide.',
                    ]),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'required'      => true,
                'type'          => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe ne correspondent pas. ',
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'help' => '<ul>
                                <li>Votre mot de passe ne peut pas trop ressembler à vos autres informations personnelles.</li>
                                <li>Votre mot de passe doit contenir au minimum 9 caractères.</li>
                                <li>Votre mot de passe ne peut pas être un mot de passe couramment utilisé.</li>
                                <li>Votre mot de passe ne peut pas être entièrement numérique.</li>
                                </ul>',
                    'help_html' => true,
                    'toggle' => true,
                    'hidden_label' => 'Cacher',
                    'visible_label' => 'Montrer',
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                    'help' => 'Saisissez le même mot de passe que précédemment, pour vérification.',
                    'toggle' => true,
                    'hidden_label' => 'Cacher',
                    'visible_label' => 'Montrer',
                ],
                'label'          => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
