<?php

namespace App\Form\Organization;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationInvitation;
use App\Entity\User\User;
use App\Service\User\UserService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationInvitationSendType extends AbstractType
{
    public function __construct(
        private UserService $userService
    )
    {
        
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->userService->getUserLogged();

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

        if ($user instanceof User) {
            $builder
                ->add('organization', EntityType::class, [
                    'label' => 'Orgnanisation pour laquelle vous souhaitez inviter cette personne',
                    'required' => true,
                    'class' => Organization::class,
                    'choice_label' => 'name',
                    'placeholder' => 'Choisissez une organisation',
                    'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($user) {
                        return $er->createQueryBuilder('o')
                            ->innerJoin('o.organizationAccesses', 'organizationAccesses')
                            ->where('organizationAccesses.user = :user')
                            ->andWhere('organizationAccesses.administrator = 1')
                            ->setParameter('user', $user)
                            ->orderBy('o.name', 'ASC')
                        ;
                    }
                ])
            ;
        
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrganizationInvitation::class,
        ]);
    }
}
