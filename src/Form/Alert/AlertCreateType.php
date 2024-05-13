<?php

namespace App\Form\Alert;

use App\Entity\Alert\Alert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AlertCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $frequenciesChoices = [];
        foreach (Alert::FREQUENCIES as $frequency) {
            $frequenciesChoices[$frequency['name']] = $frequency['slug'];
        }

        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'Donnez un nom à votre alerte',
                'sanitize_html' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir un titre pour votre alerte.',
                    ]),
                ],
            ])
            ->add('alertFrequency', ChoiceType::class, [
                'required' => true,
                'label' => 'Fréquence de l’alerte',
                'help' => 'À quelle fréquence souhaitez-vous recevoir les nouveaux résultats ?',
                'choices' => $frequenciesChoices,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner une fréquence.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Alert::class,
        ]);
    }
}
