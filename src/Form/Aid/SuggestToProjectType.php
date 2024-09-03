<?php

namespace App\Form\Aid;

use App\Entity\Organization\Organization;
use App\Entity\User\User;
use App\Service\User\UserService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count as ConstraintsCount;
use Symfony\Component\Validator\Constraints as Assert;

class SuggestToProjectType extends AbstractType
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->userService->getUserLogged();
        $organization = ($user instanceof User) ? $user->getDefaultOrganization() : null;
        $projectFavoriteChoices = [];
        if ($organization instanceof Organization) {
            foreach ($organization->getFavoriteProjects() as $favoriteProject) {
                $projectFavoriteChoices[$favoriteProject->getName()] = $favoriteProject->getId();
            }
        }
        $builder
            ->add('projectFavorites', ChoiceType::class, [
                'required' => true,
                'label' => false,
                'choices' => $projectFavoriteChoices,
                'label' => ' Liste des vos projets favoris',
                'help' => 'Cochez au moins un projet favori dans la liste pour suggérer cette aide.',
                'constraints' => [
                    new ConstraintsCount(null, 1)
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('message', TextareaType::class, [
                'required' => true,
                'label' => 'Message',
                'data' => 'Bonjour, je vous recommande cette aide qui semble convenir à votre projet.',
                'attr' => [
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir un message.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
