<?php

namespace App\Form\User;

use App\Service\User\UserService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteType extends AbstractType
{
    public function __construct(
        protected UserService $userService,
        protected UserPasswordHasherInterface $userPasswordHasherInterface
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'required' => true,
                'label' => 'Veuillez confirmer votre mot de passe',
                'attr' => [
                    'autocomplete' => 'off'
                ],
                'toggle' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez confirmer votre mot de passe.',
                    ]),
                ]
            ])
            ->add('accept', CheckboxType::class, [
                'required' => true,
                'label' => 'Je confirme la suppression <strong>définitive</strong> '
                                . 'de <strong>toutes</strong> mes données. '
                                . '<strong>Elle ne seront pas récupérables.</strong>',
                'label_html' => true,
                'constraints' => [
                    new Assert\IsTrue([
                        'message' => 'Veuillez confirmer la suppression.',
                    ]),
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
        $user = $this->userService->getUserLogged();
        if (!$this->userPasswordHasherInterface->isPasswordValid(
            $user,
            $event->getForm()->get('password')->getData()
        )
        ) {
            $event->getForm()->get('password')
                ->addError(new FormError('Le mot de passe est invalide'));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
