<?php

namespace App\Form\User\Notification;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationDeleteAllType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // extends type de base
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
