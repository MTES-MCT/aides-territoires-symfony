<?php

namespace App\Form\Project;

use App\Entity\Project\Project;
use App\Service\User\UserService;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAidToProjectType extends AbstractType
{
    public function __construct(
        protected UserService $userService,
        protected ManagerRegistry $managerRegistry
    )
    {   
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->userService->getUserLogged();
        $currentAid = $options['currentAid'] ?? null;
        $organizationProjects = [];
        if ($user->getDefaultOrganization()) {
            $organizationProjects = $this->managerRegistry->getRepository(Project::class)->findBy(['organization' => $user->getDefaultOrganization()]);
        }

        if (count($organizationProjects) > 0) {
            $builder
            ->add('projects', EntityType::class, [
                'required' => false,
                'label' => false,
                'class' => Project::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->createQueryBuilder('p')
                    ->andWhere('p.organization = :organization')
                    ->setParameter('organization', $user->getDefaultOrganization())
                    ;
                },
                'choice_attr' => function($project) use ($currentAid) {
                    foreach ($project->getAidProjects() as $aidProject) {
                        if ($aidProject->getAid()->getId() == $currentAid->getId()) {
                            return ['disabled' => true, 'checked' => 'checked'];
                        }
                    }
                    return [];
                },
                'multiple' => true,
                'expanded' => true,
            ])
        ;
        }
        $builder->add('newProject', TextType::class, [
            'required' => false,
            'label' => false,
            'sanitize_html' => true,
        ]);
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'currentAid' => null,
        ]);
    }
}
