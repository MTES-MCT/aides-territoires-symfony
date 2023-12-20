<?php

namespace App\Form\Organization;

use App\Entity\Organization\OrganizationInvitation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationInvitationSendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Son prénom'
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Son nom'
            ])
            ->add('email', EmailType::class, [
                'label' => 'Son adresse e-mail',
                'help' => 'Par exemple : prenom.nom@domaine.fr'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrganizationInvitation::class,
        ]);
    }
}
