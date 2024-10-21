<?php

namespace App\Form\User\Project;

use App\Entity\Project\Project;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectDeleteType extends AbstractType
{
    public function __construct(
        protected UserService $userService,
        protected ManagerRegistry $managerRegistry
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idProject', HiddenType::class, [
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
        // verifie que le project existe
        $project = $this->managerRegistry->getRepository(Project::class)
            ->find($event->getForm()->get('idProject')->getData());
        if (!$project instanceof Project) {
            $event->getForm()->get('idProject')->addError(new FormError('Ce projet n\'existe pas'));
        }

        // verifie que le project appartient bien à l'utilisateur
        if ($project->getAuthor() != $this->userService->getUserLogged()) {
            $event->getForm()->get('idProject')
                ->addError(
                    new FormError(
                        'Ce projet ne vous appartient pas, vous ne pouvez pas le supprimer'
                    )
                );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
