<?php

namespace App\Form\User;

use App\Entity\Organization\Organization;
use App\Service\User\UserService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrganizationChoiceType extends AbstractType
{
    public function __construct(
        private UserService $userService
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organization', EntityType::class, [
                'required' => true,
                'label' => 'Choix de la structure',
                'class' => Organization::class,
                'choice_label' => 'name',
                'query_builder' => function(EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('o')
                    ->innerJoin('o.beneficiairies', 'beneficiairies')
                    ->andWhere('beneficiairies = :user')
                    ->setParameter('user', $this->userService->getUserLogged())
                    ->orderBy('o.name', 'ASC')
                    ;
                },
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez coisir la structure.',
                    ]),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
