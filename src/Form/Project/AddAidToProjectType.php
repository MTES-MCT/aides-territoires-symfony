<?php

namespace App\Form\Project;

use App\Entity\Project\Project;
use App\Entity\User\User;
use App\Service\Organization\OrganizationService;
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
        protected ManagerRegistry $managerRegistry,
        protected OrganizationService $organizationService
    )
    {   
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // le user
        $user = $this->userService->getUserLogged();

        // l'aide
        $currentAid = $options['currentAid'] ?? null;

        // on va recuperer tous les projets de toutes les organizations ou l'utilisateur à  les droits
        $organizations = $this->userService->getOrganizations($user);
        $organizationProjects = [];
        $idsProject = [];
        foreach ($organizations as $organization) {
            if ($this->organizationService->canEditProject($user, $organization)) {
                foreach ($organization->getProjects() as $project) {
                    $organizationProjects[] = $project;
                    $idsProject[] = $project->getId();
                }
            }
        }

        if (count($idsProject) > 0) {
            $builder
            ->add('projects', EntityType::class, [
                'required' => false,
                'label' => false,
                'class' => Project::class,
                'choice_label' => function (Project $project) {
                    $return = $project->getName();
                    if ($project->getOrganization()) {
                        $return .= ' ('.$project->getOrganization()->getName().')';
                    }
                    return $return;
                },
                'query_builder' => function (EntityRepository $er) use ($idsProject) {
                    return $er->createQueryBuilder('p')
                    ->andWhere('p.id IN (:idsProject)')
                    ->setParameter('idsProject', $idsProject)
                    ->leftJoin('p.organization', 'organization')
                    ->orderBy('organization.name', 'ASC')
                    ->addOrderBy('p.name', 'ASC')
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
