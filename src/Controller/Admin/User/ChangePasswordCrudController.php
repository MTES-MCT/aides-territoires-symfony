<?php

namespace App\Controller\Admin\User;

use App\Entity\User\User;
use App\Validator\Password;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordCrudController extends AbstractCrudController implements EventSubscriberInterface
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function __construct(
        public UserPasswordHasherInterface $userPasswordHasherInterface,
        public RequestStack $requestStack,
        public AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity()->getInstance();
        if (!$entity instanceof User && !$entity->getId()) {
            return [];
        }

        yield TextField::new('newPassword', 'Nouveau mot de passe pour l\'utilisateur ' . $entity->getEmail())
            ->setFormType(PasswordType::class)
            ->setFormTypeOptions([
                'constraints' => [
                    new Password(),
                ],
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                ]
            ])
            ->onlyOnForms();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['passwordPersistEvent'],
            BeforeEntityUpdatedEvent::class => ['passwordUpdateEvent'],
        ];
    }

    public function passwordPersistEvent(BeforeEntityPersistedEvent $event)
    {
        $newPassword = $this->requestStack->getCurrentRequest()->get('User')['newPassword'] ?? null;

        $this->hashPassword(
            $event->getEntityInstance(),
            $newPassword
        );
    }

    public function passwordUpdateEvent(BeforeEntityUpdatedEvent $event)
    {
        $newPassword = $this->requestStack->getCurrentRequest()->get('User')['newPassword'] ?? null;

        $this->hashPassword(
            $event->getEntityInstance(),
            $newPassword
        );
    }

    public function hashPassword($entity, $newPassword)
    {
        if (!$entity instanceof User || !$newPassword) {
            return;
        }

        $entity->setPassword($this->userPasswordHasherInterface->hashPassword($entity, $newPassword));

        $this->addFlash('success', 'Le mot de passe a bien été modifié.');
    }
}
