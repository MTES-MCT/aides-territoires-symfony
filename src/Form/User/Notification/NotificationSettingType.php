<?php

namespace App\Form\User\Notification;

use App\Entity\Organization\Organization;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Entity\User\UserGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
