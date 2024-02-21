<?php

namespace App\Form\User\Notification;

use App\Entity\User\Notification;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationDeleteType extends AbstractType
{
    public function __construct(
        protected UserService $userService,
        protected ManagerRegistry $managerRegistry
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('idNotification', HiddenType::class, [
            'required' => true,
            'label' => false
        ])

        ->addEventListener(
            FormEvents::SUBMIT,
            [$this, 'onSubmit']
        )
        ;
    }

    public function onSubmit(FormEvent $event): void
    {
        // verifie que la notification existe
        $notification = $this->managerRegistry->getRepository(Notification::class)->find($event->getForm()->get('idNotification')->getData());
        if (!$notification instanceof Notification) {
            $event->getForm()->get('idNotification')->addError(new FormError('Cette notification n\'existe pas'));
        } else {
            // verifie que la notification appartient bien à l'utilisateur
            if (!$notification->getUser() || $notification->getUser() != $this->userService->getUserLogged()) {
                $event->getForm()->get('idNotification')->addError(new FormError('Cette notification ne vous appartient pas, vous ne pouvez pas la supprimer'));
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
