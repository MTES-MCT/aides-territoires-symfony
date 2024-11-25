<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Repository\Aid\AidRepository;
use App\Repository\Organization\OrganizationRepository;
use App\Repository\Project\ProjectRepository;
use App\Service\User\UserService;
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
    ): Response {
        /* @var User $user */
        $user = $userService->getUserLogged();
        $aidsNumber = 0;
        if ($user->isIsContributor()) {
            $aidsNumber = $aidRepository->countByUserOrganizations($userService->getUserLogged());
        }
        $projectsNumber = $projectRepository->countByOrganization($user->getDefaultOrganization());
        $collaboratorsNumber = $organizationRepository->countCollaborators($userService->getUserLogged());

        $this->breadcrumb->add("Mon compte", null);
        return $this->render('user/dashboard/index.html.twig', [
            'aidsNumber' => $aidsNumber,
            'projectsNumber' => $projectsNumber,
            'collaboratorsNumber' => $collaboratorsNumber
        ]);
    }
}
