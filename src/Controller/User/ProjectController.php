<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Form\Project\ProjectEditType;
use App\Form\User\Project\ProjectDeleteType;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\Project\ProjectValidatedRepository;
use App\Service\Image\ImageService;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class ProjectController extends FrontController
{
    const NB_PROJECT_BY_PAGE = 30;


    #[Route('/comptes/projets/', name: 'app_user_project_structure')]
    public function index(
        UserService $userService,
        ProjectRepository $projectRepository,
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry
    ): Response
    {
        $user = $userService->getUserLogged();

        // formulaire suppression projet
        $formDeleteProject = $this->createForm(ProjectDeleteType::class, null, [
            'action' => $this->generateUrl('app_user_project_structure')
        ]);
        $formDeleteProject->handleRequest($requestStack->getCurrentRequest());
        if ($formDeleteProject->isSubmitted()) {
            if ($formDeleteProject->isValid()) {
                // suppression
                $managerRegistry->getManager()->remove($projectRepository->find($formDeleteProject->get('idProject')->getData()));
                $managerRegistry->getManager()->flush();

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Le projet a bien été supprimé.'
                );

                // redirection
                return $this->redirectToRoute('app_user_project_structure');
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Vous ne pouvez pas supprimer ce projet.'
                );
            }
        }

        // projets
        $projects = $projectRepository->findBy(
            [
                'author' => $user
                // 'organization' => $user->getDefaultOrganization()
            ],
            [
                'timeCreate' => 'DESC'
            ]
        );

        // fil arianne
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets de ma structure");
        
        // rendu template
        return $this->render('user/project/index.html.twig', [
            'projects' => $projects,
            'formDeleteProject' => $formDeleteProject
        ]);
    }

    #[Route('/comptes/projets-favoris/', name: 'app_user_project_favoris')]
    public function projetFavoris(UserService $userService): Response
    {
        $user = $userService->getUserLogged();
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets favoris");
        return $this->render('user/project/favoris.html.twig', [
            'user' => $user
        ]);
    }

    
    #[Route('/comptes/projets/details/{id}-{slug}/', name: 'app_user_project_details_fiche_projet', requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+'])]
    public function details(
        $id,
        $slug,
        ProjectRepository $ProjectRepository,
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        ImageService $imageService
    ): Response
    {
        $project = $ProjectRepository->findOneBy(
            [
                'id' => $id,
                'slug' => $slug
            ]
        );
        $user = $userService->getUserLogged();
        
        if (!$project instanceof Project || !$userService->isMemberOfOrganization($project->getOrganization(), $user)) {
            return $this->redirectToRoute('app_user_project_structure');
        }
        
        $form = $this->createForm(ProjectEditType::class, $project);

        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // traitement image
                $imageFile = $form->get('imageUploadedFile')->getData();
                if ($imageFile instanceof UploadedFile) {
                    $project->setImage($imageService->getSafeFileName($imageFile->getClientOriginalName()));
                    $imageService->sendUploadedImageToCloud($imageFile, Project::FOLDER, $project->getImage());
                }

                // si demande de projet public
                if ($project->getStatus() == Project::STATUS_DRAFT && $project->isIsPublic()) {
                    $project->setStatus(Project::STATUS_REVIEWABLE);
                }

                // sauvegarde
                $managerRegistry->getManager()->persist($project); 
                $managerRegistry->getManager()->flush();

                // notification
                $this->tAddFlash(
                    FrontController::FLASH_SUCCESS,
                    'Vos modifications ont été enregistrées avec succès.'
                );

                // redirection
                return $this->redirectToRoute('app_user_project_details_fiche_projet', [
                    'id' => $id,
                    'slug' => $slug
                ]);
            } else {
                $formErrors = true;
            }
        }

        // fil arianne
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Projet ".$project->getName(),null);

        // rendu template
        return $this->render('user/project/fiche_projet.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'formErrors' => $formErrors ?? false
        ]);
    }

    #[Route('/comptes/projets/creation/', name: 'app_user_project_creation_projet')]
    public function creation(
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        ImageService $imageService
    ): Response
    {
        $project = new Project();
        $user = $userService->getUserLogged();
        
        // formulaire
        $form = $this->createForm(ProjectEditType::class, $project);
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // traitement image
                $imageFile = $form->get('imageUploadedFile')->getData();
                if ($imageFile instanceof UploadedFile) {
                    $project->setImage($imageService->getSafeFileName($imageFile->getClientOriginalName()));
                    $imageService->sendUploadedImageToCloud($imageFile, Project::FOLDER, $project->getImage());
                }

                // données additionnelles
                $project->setStatus(Project::STATUS_DRAFT);
                $project->setOrganization($user->getDefaultOrganization());
                $project->setAuthor($user);
                
                // si demande de projet public
                if ($project->getStatus() == Project::STATUS_DRAFT && $project->isIsPublic()) {
                    $project->setStatus(Project::STATUS_REVIEWABLE);
                }

                // sauvegarde
                $managerRegistry->getManager()->persist($project); 
                $managerRegistry->getManager()->flush();

                // notification
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Votre projet a bien été créé, vous pouvez maintenant chercher des aides.'
                );

                // redirection
                return $this->redirectToRoute('app_user_project_aides', [
                    'id' => $project->getId(),
                    'slug' => $project->getSlug()
                ]);
            } else {
                $formErrors = true;
            }
        }

        // fil arianne
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Nouveau projet",null);

        // rendu termplate
        return $this->render('user/project/creation_projet.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'formErrors' => $formErrors ?? false
        ]);
    }


    #[Route('/comptes/projets/aides/{id}-{slug}/', name: 'app_user_project_aides', requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+'])]
    public function aides(
        $id,
        ProjectRepository $ProjectRepository,
        RequestStack $requestStack,
        UserService $userService,
    ): Response
    {
        $project = $ProjectRepository->findOneBy(
            [
                'id' => $id
            ]
        );
        $user = $userService->getUserLogged();
        
        if (!$project instanceof Project || $project->getOrganization()->getId()!=$user->getDefaultOrganization()->getId()) {
            return $this->redirectToRoute('app_user_project_structure');
        }

        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Projet ".$project->getName(),null);

        return $this->render('user/project/aides.html.twig', [
            'project' => $project
        ]);
    }


    #[Route('/comptes/projets/similaires/{id}-{slug}/', name: 'app_user_project_similaires', requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+'])]
    public function similaires(
        $id,
        $slug,
        ProjectRepository $ProjectRepository,
        UserService $userService,
        ProjectValidatedRepository $projectValidatedRepository,
        ProjectRepository $projectRepository,
        RequestStack $requestStack,
        ReferenceService $referenceService
    ): Response
    {
        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);
        
        $project = $ProjectRepository->findOneBy(
            [
                'id' => $id,
                'slug' => $slug
            ]
        );
        $user = $userService->getUserLogged();
        
        if (!$project instanceof Project || $project->getOrganization()->getId()!=$user->getDefaultOrganization()->getId()) {
            return $this->redirectToRoute('app_user_project_structure');
        }

        // Projets subventionnés
        $synonyms = ($project->getProjectReference())
            ? $referenceService->getSynonymes($project->getProjectReference()->getName())
            : null
        ;

        $project_perimeter = ($user->getDefaultOrganization() && $user->getDefaultOrganization()->getPerimeter())
            ? $user->getDefaultOrganization()->getPerimeter()
            : null
        ;
        $projects = [];
        if ($project_perimeter instanceof Perimeter) {
            $projectParams = [
                'perimeter' => $project_perimeter,
                'radius' => 30
            ];
            if ($synonyms) {
                $projectParams = array_merge($projectParams, $synonyms);
            }
            $projects = $projectValidatedRepository->findProjectInRadius($projectParams);
        }

        // pagination project validés
        $adapter = new ArrayAdapter($projects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_PROJECT_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        
        // Projets publics : 
        $projets_publics = [];
        if ($synonyms) {
            $projectParams = $synonyms;
            $projectParams['exclude'] = $project;
            $projectsParams['limit'] = 12;
            $projectsParams['orderBy'] = [
                'sort' => 'p.timeCreate',
                'order' => 'DESC'
            ];
            if ($project_perimeter instanceof Perimeter) {
                $projectParams['perimeterRadius'] = $project_perimeter;
                $projectParams['radius'] = 30;
            }

            $projets_publics = $projectRepository->findPublicProjects($projectParams);

            // Si rien à 30 km, on élargit à 300 km
            if (count($projets_publics) == 0 && $project_perimeter instanceof Perimeter) {
                $projectParams['radius'] = 300;
                $projets_publics = $projectRepository->findPublicProjects($projectParams);
            }
        }   
        
        // fil d'arianne
        $this->breadcrumb->add("Mon compte",$this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add("Mes projets");
        $this->breadcrumb->add("Projet ".$project->getName(),null);

        // rendu template
        return $this->render('user/project/similaires.html.twig', [
            'project' => $project,
            'projets_publics' => $projets_publics,
            'myPager' => $pagerfanta,
        ]);
    }
}
