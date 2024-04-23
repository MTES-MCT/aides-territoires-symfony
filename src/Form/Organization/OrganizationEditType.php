<?php

namespace App\Form\Organization;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Organization\OrganizationTypeGroup;
use App\Form\Type\PerimeterAutocompleteType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class OrganizationEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // les choix pour intercommunality type
        $choicesIntercommunalityType = [];
        foreach (Organization::INTERCOMMUNALITY_TYPES as $intercommunalityType) {
            $choicesIntercommunalityType[$intercommunalityType['name']] = $intercommunalityType['slug'];
        }
        
        $builder
            ->add('organizationType', EntityType::class, [
                'required' => true,
                'label' => 'Type de la structure',
                'class' => OrganizationType::class,
                'choice_label' => 'name',
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('ot')
                        ->orderBy('ot.name', 'ASC');
                },
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('intercommunalityType', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Type d’intercommunalité', 
                'placeholder' => 'Sélectionnez une valeur',
                'choices' => $choicesIntercommunalityType,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('perimeter', PerimeterAutocompleteType::class, [
                'required' => true,
                'label' => 'Territoire de la structure',
                'help' => 'Tous les périmètres géographiques sont disponibles : CA, CU, CC, pays, parc, etc. Contactez-nous si vous ne trouvez pas le vôtre.',
                'placeholder' => 'Tapez les premiers caractères',
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Nom de la structure',
                'help' => 'En fonction des informations saisies précédemment, nous pouvons, parfois pré-remplir ce champ automatiquement. Vous pouvez cependant corriger le nom proposé si besoin.',
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse postale',
                'required' => true,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('cityName', TextType::class, [
                'label' => 'Ville',
                'required' => true,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal',
                'required' => true,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('sirenCode', TextType::class, [
                'label' => 'Code SIREN',
                'required' => false,
                'help' => 'constitué de 9 chiffres',
                'constraints' => [
                    new Length(9)
                ],
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('siretCode', TextType::class, [
                'label' => 'Code SIRET',
                'required' => false,
                'help' => 'constitué de 14 chiffres',
                'constraints' => [
                    new Length(14)
                ],
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('apeCode', TextType::class, [
                'label' => 'Code APE',
                'required' => false,
                'constraints' => [
                    new Length(max: 10)
                ],
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('inseeCode', TextType::class, [
                'label' => 'Code INSEE',
                'required' => false,
                'constraints' => [
                    new Length(5)
                ],
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])

            ->addEventListener(
                FormEvents::SUBMIT,
                [$this, 'onSubmit']
            )
        ;
    }

    public function onSubmit(FormEvent $event): void
    {
        // si on a le type d'organization
        if ($event->getForm()->has('organizationType') && $event->getForm()->get('organizationType')->getData()) {
            // si c'est une collectivité
            if ($event->getForm()->get('organizationType')->getData()->getOrganizationTypeGroup() && $event->getForm()->get('organizationType')->getData()->getOrganizationTypeGroup()->getId() == OrganizationTypeGroup::ID_COLLECTIVITES) {
                //  le code insee est alors obligatoire
                if ($event->getForm()->has('inseeCode') && !$event->getForm()->get('inseeCode')->getData()) {
                    $event->getForm()->get('inseeCode')->addError(new FormError('Le code INSEE est obligatoire pour les collectivités'));
                }
            }
        }
                
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organization::class,
            'is_readonly' => false
        ]);
    }
}
