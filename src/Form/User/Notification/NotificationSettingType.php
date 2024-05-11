<?php

namespace App\Form\User\Notification;

use App\Entity\User\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class NotificationSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notificationEmailFrequency', ChoiceType::class, [
                'required' => true,
                'label' => 'Fréquence d’envoi des emails de notifications',
                'choices' => [
                    'Chaque jour' => User::NOTIFICATION_DAILY,
                    'Chaque semaine' => User::NOTIFICATION_WEEKLY,
                    'Jamais' => User::NOTIFICATION_NEVER,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez choisir la fréquence d\'envoi.',
                    ]),
                ]
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
