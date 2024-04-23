<?php

namespace App\Form\User;

use App\Entity\Organization\Organization;
use App\Entity\User\User;
use App\Service\User\UserService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransfertProjectType extends AbstractType
{
    public function __construct(
        protected UserService $userService
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['organization'] instanceof Organization) {
            $builder
                ->add('user', EntityType::class, [
                    'required' => true,
                    'label' => 'Transférer les projets à :',
                    'class' => User::class,
                    'choice_label' => function (User $user) {
                        return $user->getFullName() . ' (' . $user->getEmail() . ')';
                    },
                    'query_builder' => function ($er) use ($options) {
                        return $er->createQueryBuilder('u')
                            ->innerJoin('u.organizationAccesses', 'organizationAccesses')
                            ->andWhere('organizationAccesses.organization = :organization')
                            ->andWhere('u != :user')
                            ->setParameter('organization', $options['organization'])
                            ->setParameter('user', $this->userService->getUserLogged())
                            ->orderBy('u.lastname', 'ASC')
                            ->addOrderBy('u.firstname', 'ASC')
                        ;
                    }
                ])
                ->add('idOrganization', HiddenType::class, [
                    'required' => true,
                    'data' => $options['organization']->getId(),
                    'allow_extra_fields' => true
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'organization' => null,
        ]);
    }
}
