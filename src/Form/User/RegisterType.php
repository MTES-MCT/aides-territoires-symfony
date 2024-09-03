<?php

namespace App\Form\User;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Form\Type\PerimeterAutocompleteType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // les choices pour acquisition channel
        $choicesAcquisitionChannel = [];
        foreach (User::ACQUISITION_CHANNEL_CHOICES as $channel) {
            $choicesAcquisitionChannel[$channel['name']] = $channel['slug'];
        }

        // les choix pour intercommunality type
        $choicesIntercommunalityType = [];
        foreach (Organization::INTERCOMMUNALITY_TYPES as $intercommunalityType) {
            $choicesIntercommunalityType[$intercommunalityType['name']] = $intercommunalityType['slug'];
        }

        // choix beneficiaryFunction
        $choicesBeneficiaryFunction = [];
        foreach (User::FUNCTION_TYPES as $functionType) {
            $choicesBeneficiaryFunction[$functionType['name']] = $functionType['slug'];
        }

        $builder
            ->add('firstname', TextType::class, [
                'required' => true,
                'label' => 'Votre prénom',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre prénom.',
                    ]),
                ]
            ])
            ->add('lastname', TextType::class, [
                'required' => true,
                'label' => 'Votre nom',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre nom.',
                    ]),
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'Votre adresse e-mail',
                'help' => 'Par exemple : prenom.nom@domaine.fr<br />Nous enverrons un e-mail de confirmation à cette adresse avant de valider le compte.',
                'help_html' => true,
                'attr' => [
                    'placeholder' => 'Merci de bien vérifier l\'adresse saisie'
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
            ->add('organizationType', EntityType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Type de votre structure',
                'placeholder' => 'Sélectionnez une valeur',
                'class' => OrganizationType::class,
                'group_by' => function (OrganizationType $organizationType) {
                    return $organizationType->getOrganizationTypeGroup()->getName();
                },
                'choice_label' => 'name'
            ])
            ->add('intercommunalityType', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Type d’intercommunalité',
                'placeholder' => 'Sélectionnez une valeur',
                'choices' => $choicesIntercommunalityType
            ])
            ->add('perimeter', PerimeterAutocompleteType::class, [
                'required' => true,
                'label' => 'Votre territoire',
                'help' => 'Tous les périmètres géographiques sont disponibles : CA, CU, CC, pays, parc, etc. Contactez-nous si vous ne trouvez pas le vôtre.',
                'placeholder' => 'Tapez les premiers caractères',
                'class' => Perimeter::class,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez choisir votre territoire.',
                    ]),
                ],
            ])
            ->add('organizationName', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Nom de votre structure',
                'help' => 'En fonction des informations saisies précédemment, nous pouvons, parfois pré-remplir ce champ automatiquement. Vous pouvez cependant corriger le nom proposé si besoin.',
                // 'sanitize_html' => true,
            ])
            ->add('beneficiaryFunction', ChoiceType::class, [
                'required' => false,
                'label' => 'Votre fonction',
                'placeholder' => 'Sélectionnez votre fonction',
                'choices' => $choicesBeneficiaryFunction
            ])
            ->add('beneficiaryRole', TextType::class, [
                'required' => false,
                'label' => 'Votre rôle',
            ])
            ->add('isBeneficiary', CheckboxType::class, [
                'required' => false,
                'label' => 'Trouver des aides'
            ])
            ->add('isContributor', CheckboxType::class, [
                'required' => false,
                'label' => 'Publier des aides'
            ])
            ->add('acquisitionChannel', ChoiceType::class, [
                'required' => false,
                'label' => 'Comment avez-vous connu Aides-territoires ?',
                'placeholder' => 'Sélectionnez une option',
                'choices' => $choicesAcquisitionChannel
            ])
            ->add('acquisitionChannelComment', TextType::class, [
                'required' => false,
                'label' => 'Précisez comment vous avez connu Aides-territoires',
            ])
            ->add('mlConsent', CheckboxType::class, [
                'required' => false,
                'label' => 'Je souhaite recevoir la lettre d\'information mensuelle'
            ])
        ;

        if ($options['onlyOrganization']) {
            $builder
                ->remove('firstname')
                ->remove('lastname')
                ->remove('email')
                ->remove('password')
                ->remove('acquisitionChannel')
                ->remove('acquisitionChannelComment')
                ->add('address', TextType::class, [
                    'label' => 'Adresse postale',
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Veuillez saisir votre adresse postale.',
                        ]),
                    ],
                ])
                ->add('cityName', TextType::class, [
                    'label' => 'Ville',
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Veuillez saisir votre ville.',
                        ]),
                    ],
                ])
                ->add('zipCode', TextType::class, [
                    'label' => 'Code postal',
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Veuillez saisir votre code postal.',
                        ]),
                    ],
                ])
                ->add('sirenCode', TextType::class, [
                    'label' => 'Code SIREN',
                    'required' => false,
                    'help' => 'constitué de 9 chiffres',
                    'mapped' => false,
                    'constraints' => [
                        new Length(9)
                    ],
                ])
                ->add('siretCode', TextType::class, [
                    'label' => 'Code SIRET',
                    'required' => false,
                    'help' => 'constitué de 14 chiffres',
                    'mapped' => false,
                    'constraints' => [
                        new Length(14)
                    ],
                ])
                ->add('apeCode', TextType::class, [
                    'label' => 'Code APE',
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [
                        new Length(max: 10)
                    ],
                ])
                ->add('inseeCode', TextType::class, [
                    'label' => 'Code INSEE',
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [
                        new Length(5),
                        new Assert\NotBlank([
                            'message' => 'Veuillez saisir votre code INSEE.',
                        ]),
                    ],
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'onlyOrganization' => false,
        ]);
    }
}
