<?php

namespace App\Form\Project;

use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Project\Project;
use App\Entity\Reference\KeywordReference;
use App\Form\Type\KeywordSynonymlistAutocompleteField;
use App\Form\Type\PerimeterCityAutocompleteType;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectValidatedSearchType extends AbstractType
{
    public function  __construct(
        protected ManagerRegistry $managerRegistry,
        protected UserService $userService
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->userService->getUserLogged();

        $statuses = [];
        foreach (Project::PROJECT_STEPS as $status) {
            $statuses[$status['name']] = $status['slug'];
        }

        $contactLinks = [];
        foreach (Project::CONTRACT_LINK as $contactLink) {
            $contactLinks[$contactLink['name']] = $contactLink['slug'];
        }

        // Perimeter params
        $perimeterParams = [
            'required' => true,
            'label' => 'Commune du projet',
        ];
        if ($options['forcePerimeter'] !== false) {
            $perimeterParams['data'] = $options['forcePerimeter'];
        } else {
            if (!$options['dontUseUserPerimeter']) {
                $perimeterParams['data'] = ($user && $user->getDefaultOrganization() && $user->getDefaultOrganization()->getPerimeter()) ? $user->getDefaultOrganization()->getPerimeter() : null;
            }
        }


        $builder
            ->add('project_perimeter', PerimeterCityAutocompleteType::class, $perimeterParams)
            ->add('text', EntityType::class,  [
                'required' => false,
                'label' => 'Mot-clés',
                'attr' => [
                    'placeholder' => 'Ex: rénovation énergétique, vélo, tiers lieu, etc.'
                ],
                'class' => KeywordReference::class,
                'autocomplete' => true,
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('k')
                        ->andWhere('k.intention = 0')
                        ->andWhere('k.parent = k')
                        ->orderBy('k.name', 'ASC');
                },
                'choice_label' => function(KeywordReference $keywordReference) {
                    return ucfirst($keywordReference->getName());
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'forcePerimeter' => false,
            'dontUseUserPerimeter' => false
        ]);
    }
}
