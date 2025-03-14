<?php

namespace App\Form\User\Aid;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AidStatsPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateMin', DateType::class, [
                'required' => true,
                'label' => 'Date de début',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner une date de début.',
                    ]),
                ],
            ])
            ->add('dateMax', DateType::class, [
                'required' => true,
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner une date de fin.',
                    ]),
                ],
            ])
        ;

        // ajoute ecouteur sur le submit
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $dateMin = $form->get('dateMin')->getData();
            $dateMax = $form->get('dateMax')->getData();

            if ($dateMin && $dateMax) {
                $interval = $dateMin->diff($dateMax);
                if ($interval->days > 93) {
                    $form->get('dateMin')->addError(
                        new FormError(
                            'La période entre la date de début et la date de fin ne doit pas dépasser trois mois.'
                        )
                    );
                    $form->get('dateMax')->addError(
                        new FormError(
                            'La période entre la date de début et la date de fin ne doit pas dépasser trois mois.'
                        )
                    );
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
