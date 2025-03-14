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
use Symfony\Component\Validator\Constraints as Assert;

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
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez choisir le type de votre structure.',
                    ]),
                ],
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
                'label' => 'Territoire de la structure',
                'help' => 'Tous les périmètres géographiques sont disponibles : '
                                . 'CA, CU, CC, pays, parc, etc. '
                                . 'Contactez-nous si vous ne trouvez pas le vôtre.',
                'placeholder' => 'Tapez les premiers caractères',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez choisir le territoire de la structure.',
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Nom de la structure',
                'help' => 'En fonction des informations saisies précédemment, '
                            . 'nous pouvons, parfois pré-remplir ce champ automatiquement. '
                            . 'Vous pouvez cependant corriger le nom proposé si besoin.',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir le nom de la structure.',
                    ]),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse postale',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir l\'adresse postale de votre structure.',
                    ]),
                ],
            ])
            ->add('cityName', TextType::class, [
                'label' => 'Ville',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir la ville de votre structure.',
                    ]),
                ],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir le code postal de votre structure.',
                    ]),
                ],
            ])
            ->add('sirenCode', TextType::class, [
                'label' => 'Code SIREN',
                'required' => false,
                'help' => 'constitué de 9 chiffres',
            ])
            ->add('siretCode', TextType::class, [
                'label' => 'Code SIRET',
                'required' => false,
                'help' => 'constitué de 14 chiffres',
            ])
            ->add('apeCode', TextType::class, [
                'label' => 'Code APE',
                'required' => false,
            ])
            ->add('inseeCode', TextType::class, [
                'label' => 'Code INSEE',
                'required' => false,
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
            if (
                $event->getForm()->get('organizationType')->getData()->getOrganizationTypeGroup()
                && $event->getForm()->get('organizationType')->getData()->getOrganizationTypeGroup()->getId() ==
                    OrganizationTypeGroup::ID_COLLECTIVITES
            ) {
                //  le code insee est alors obligatoire
                if ($event->getForm()->has('inseeCode') && !$event->getForm()->get('inseeCode')->getData()) {
                    $event->getForm()->get('inseeCode')
                        ->addError(new FormError('Le code INSEE est obligatoire pour les collectivités'));
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organization::class,
        ]);
    }
}
