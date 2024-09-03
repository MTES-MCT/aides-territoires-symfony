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
use Symfony\Component\Validator\Constraints as Assert;

class TransfertAidType extends AbstractType
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['organization'] instanceof Organization) {
            $builder
                ->add('user', EntityType::class, [
                    'required' => true,
                    'label' => 'Transférer les aides à :',
                    'class' => User::class,
                    'choice_label' => function (User $user) {
                        return $user->getFullName() . ' (' . $user->getEmail() . ')';
                    },
                    'query_builder' => function ($er) use ($options) {
                        return $er->createQueryBuilder('u')
                            ->innerJoin('u.organizations', 'organizations')
                            ->andWhere('organizations = :organization')
                            ->andWhere('u != :user')
                            ->setParameter('organization', $options['organization'])
                            ->setParameter('user', $this->userService->getUserLogged())
                            ->orderBy('u.lastname', 'ASC')
                            ->addOrderBy('u.firstname', 'ASC')
                        ;
                    },
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Veuillez choisir un utilisateur.',
                        ]),
                    ]
                ])
                ->add('idOrganization', HiddenType::class, [
                    'required' => true,
                    'data' => $options['organization']->getId(),
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'organization' => null
        ]);
    }
}
