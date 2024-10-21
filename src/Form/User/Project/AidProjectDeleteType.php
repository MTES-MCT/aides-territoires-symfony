<?php

namespace App\Form\User\Project;

use App\Entity\Aid\AidProject;
use App\Service\User\UserService;
use App\Entity\User\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AidProjectDeleteType extends AbstractType
{
    public function __construct(
        protected UserService $userService,
        protected ManagerRegistry $managerRegistry
    ) {
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idAidProject', HiddenType::class, [
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
        try {
            // verifie que l'aidProject existe
            /** @var AidProject $aidProject **/
            $aidProject = $this->managerRegistry->getRepository(AidProject::class)
                ->find($event->getForm()->get('idAidProject')->getData());
            if (!$aidProject instanceof AidProject) {
                $event->getForm()->get('idAidProject')->addError(new FormError('Ce aide n\'existe pas'));
            }

            // verifie que le project appartient bien à l'origanisation de l'utilisateur
            /** @var User $user **/
            $user = $this->userService->getUserLogged();
            if (
                !$user->getDefaultOrganization()
                || !$aidProject->getProject()->getOrganization()
                || !$this->userService->isMemberOfOrganization($aidProject->getProject()->getOrganization(), $user)
            ) {
                $event->getForm()->get('idAidProject')
                    ->addError(
                        new FormError(
                            'Ce projet ne vous appartient pas, vous ne pouvez pas lui supprimer d\'aide'
                        )
                    );
            }
        } catch (\Exception $e) {
            $event->getForm()->addError(new FormError('Impossible de supprimer'));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
