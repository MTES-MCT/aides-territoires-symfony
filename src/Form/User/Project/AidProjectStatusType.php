<?php

namespace App\Form\User\Project;

use App\Entity\Aid\AidProject;
use App\Entity\User\User;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AidProjectStatusType extends AbstractType
{
    public function __construct(
        protected UserService $userService,
        protected ManagerRegistry $managerRegistry
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('aidRequested', CheckboxType::class, [
                'required' => false,
                'label' => 'Aide demandée',
                'help' => 'Vous avez déposé un dossier de candidature, ou effectué une demande d’aide officielle auprès de son porteur/instructeur.'
            ])
            ->add('aidObtained', CheckboxType::class, [
                'required' => false,
                'label' => 'Aide obtenue',
                'help' => 'Vous avez été notifié par le porteur/instructeur que votre dossier/demande était accepté(e), mais n’avez pas encore bénéficié de l’aide. (L’aide ne peut être à la fois « obtenue » et « refusée »)'
            ])
            ->add('aidPaid', CheckboxType::class, [
                'required' => false,
                'label' => 'Aide reçue',
                'help' => 'Vous avez bénéficié du versement d’au moins une partie de l’aide obtenue, ou la prestation en ingénierie a été réalisée. (L’aide ne peut être à la fois « obtenue » et « refusée »)'
            ])
            ->add('aidDenied', CheckboxType::class, [
                'required' => false,
                'label' => 'Aide refusée',
                'help' => 'Vous avez été notifié par le porteur/instructeur que votre dossier/demande n’avait pas été accepté. (L’aide ne peut être à la fois « obtenue » ou « reçue » et « refusée »)'
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
            $aidProject = $event->getData();

            // verifie que le project appartient bien à l'origanisation de l'utilisateur
            /** @var User $user **/
            $user = $this->userService->getUserLogged();
            if (
                !$user->getDefaultOrganization()
                || !$aidProject->getProject()->getOrganization()
                || !$this->userService->isMemberOfOrganization($aidProject->getProject()->getOrganization(), $user)
            ) {
                $event->getForm()->get('idAidProject')->addError(new FormError('Ce projet ne vous appartient pas, vous ne pouvez pas le modifier'));
            }

            // les status passés
            $aidRequested = $event->getForm()->get('aidRequested')->getData();
            $aidObtained = $event->getForm()->get('aidObtained')->getData();
            $aidPaid = $event->getForm()->get('aidPaid')->getData();
            $aidDenied = $event->getForm()->get('aidDenied')->getData();

            // Si aide payée, elle doit forcément avoir été demandée et obtenue et ne pas être refusée
            if ($aidPaid && ($aidDenied || !$aidRequested || !$aidObtained)) {
                $event->getForm()->get('aidPaid')->addError(new FormError('L\'aide ne peut être payée que si elle a été demandée et obtenue et n\'a pas été refusée'));
            }
            // si aide refusée, elle doit foircément avoir été demandée et non obtenue et non payée
            if ($aidDenied && ($aidPaid || $aidObtained || !$aidRequested)) {
                $event->getForm()->get('aidDenied')->addError(new FormError('L\'aide ne peut être refusée que si elle a été demandée et non obtenue et non payée'));
            }

            // si aide obtenue, elle doit avoir été demandée et ne pas être refusée
            if ($aidObtained && ($aidDenied || !$aidRequested)) {
                $event->getForm()->get('aidObtained')->addError(new FormError('L\'aide ne peut être obtenue que si elle a été demandée et non refusée'));
            }
        } catch (\Exception $e) {
            $event->getForm()->addError(new FormError('Impossible de modifier'));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AidProject::class,
        ]);
    }
}
