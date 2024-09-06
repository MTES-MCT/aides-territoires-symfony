<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\Aid\AidCrudController;
use App\Entity\Aid\Aid;
use App\Entity\Reference\ProjectReference;
use App\Repository\Aid\AidRepository;
use App\Service\Aid\AidService;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProjectReferenceController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AdminUrlGenerator $adminUrlGenerator,
        private AidService $aidService
    ) {
    }

    #[Route('/admin/statistics/projets-references/dashboard', name: 'admin_statistics_project_reference_dashboard')]
    public function prDashboard(): Response
    {
        // recupere tous les projets référents
        $projectReferences = $this->managerRegistry->getRepository(ProjectReference::class)->findAll();

        // pour chaque projet, créer le lien pour aller dans la liste des aides dans l'admin
        $aidsUrlByProjectReferenceId = [];
        foreach ($projectReferences as $projectReference) {
            $aidsUrlByProjectReferenceId[$projectReference->getId()] = $this->adminUrlGenerator
                ->setController(AidCrudController::class)
                ->setAction(Action::INDEX)
                ->set('filters[projectReferences][value][]', $projectReference->getId())
                ->set('filters[projectReferences][comparison]', '=')
                ->generateUrl();
        }

        // retour template
        return $this->render('admin/statistics/project_reference/dashboard.html.twig', [
            'projectReferences' => $projectReferences,
            'aidsUrlByProjectReferenceId' => $aidsUrlByProjectReferenceId
        ]);
    }
}
