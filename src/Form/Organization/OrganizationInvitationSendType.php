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
use Symfony\Component\Validator\Constraints as Assert;

class OrganizationInvitationSendType extends AbstractType
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->userService->getUserLogged();

        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Son prénom',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir son prénom.',
                    ]),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Son nom',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir son nom.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Son adresse e-mail',
                'help' => 'Par exemple : prenom.nom@domaine.fr',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir son email.',
                    ]),
                    new Assert\Email([
                        'message' => 'L\'email "{{ value }}" n\'est pas une adresse email valide.',
                    ]),
                ],
            ])
        ;

        if ($user instanceof User) {
            $builder
                ->add('organization', EntityType::class, [
                    'label' => 'Structure pour laquelle vous souhaitez inviter cette personne',
                    'required' => true,
                    'class' => Organization::class,
                    'choice_label' => 'name',
                    'placeholder' => 'Choisissez une structure',
                    'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($user) {
                        return $er->createQueryBuilder('o')
                            ->innerJoin('o.beneficiairies', 'beneficiairies')
                            ->where('beneficiairies = :user')
                            ->setParameter('user', $user)
                            ->orderBy('o.name', 'ASC')
                        ;
                    },
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Veuillez choisir une structure.',
                        ]),
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrganizationInvitation::class,
        ]);
    }
}
