<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Repository\Aid\AidRepository;
use App\Repository\Organization\OrganizationRepository;
use App\Repository\Project\ProjectRepository;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends FrontController
{
    #[Route('/comptes/moncompte/', name: 'app_user_dashboard')]
    public function index(
        UserService $userService,
        ProjectRepository $projectRepository,
        OrganizationRepository $organizationRepository,
        AidRepository $aidRepository
        ): Response
    {   
        $user = $userService->getUserLogged();

        // les organizations du user
        $organizations = $userService->getOrganizations($user);

        // les aides du user et de ses organizations
        $aids = $aidRepository->findCustom([
            'organizations' => $organizations
        ]);

        // les projets du user
        $projects = $projectRepository->findCustom([
            'organizations' => $organizations
        ]);

        // les collaborateurs du user
        $collaborators = new ArrayCollection();
        foreach ($organizations as $organization) {
            foreach ($organization->getOrganizationAccesses() as $organizationsAccess) {
                if ($organizationsAccess->getUser() !== $user && !$collaborators->contains($organizationsAccess->getUser())) {
                    $collaborators->add($organizationsAccess->getUser());
                }
            }
        }
        
        // fil arianne
        $this->breadcrumb->add("Mon compte", null);

        // retour template
        return $this->render('user/dashboard/index.html.twig', [
            'aids' => $aids,
            'projects' => $projects,
            'nbCollaborators' => $collaborators->count()
        ]);
    }
}
