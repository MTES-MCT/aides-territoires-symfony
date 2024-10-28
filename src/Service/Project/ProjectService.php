<?php

namespace App\Service\Project;

use App\Entity\Project\Project;
use App\Entity\Project\ProjectLock;
use App\Entity\User\User;
use App\Repository\Project\ProjectRepository;
use Doctrine\Persistence\ManagerRegistry;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class ProjectService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private ManagerRegistry $managerRegistry,
        private Environment $twig
    ) {
    }

    /**
     * @param array<string, mixed>|null $projectParams
     * @return Project[]
     */
    public function searchProjects(?array $projectParams = null): array
    {
        return $this->projectRepository->findCustom($projectParams);
    }

    public function canUserLock(Project $project, User $user): bool
    {
        if (!$project->getOrganization()) {
            return false;
        }

        if ($project->getOrganization()->getBeneficiairies()->contains($user)) {
            return true;
        }


        return false;
    }

    public function getLock(Project $project): ?ProjectLock
    {
        foreach ($project->getProjectLocks() as $projectLock) {
            return $projectLock;
        }

        return null;
    }

    public function isLockedByAnother(Project $project, User $user): bool
    {
        $now = new \DateTime(date('Y-m-d H:i:s'));
        $minutesMax = 5;
        foreach ($project->getProjectLocks() as $projectLock) {
            // si le lock a plus de 5 min, on le supprime
            if ($projectLock->getTimeStart() < $now->sub(new \DateInterval('PT' . $minutesMax . 'M'))) {
                $this->managerRegistry->getManager()->remove($projectLock);
                $this->managerRegistry->getManager()->flush();
                continue;
            }
            if ($projectLock->getUser() != $user) {
                return true;
            }
        }
        return false;
    }

    public function isLocked(Project $project): bool
    {
        return !$project->getProjectLocks()->isEmpty();
    }

    public function lock(Project $project, User $user): void
    {
        if ($project->getProjectLocks()->isEmpty()) {
            $projectLock = new ProjectLock();
            $projectLock->setProject($project);
            $projectLock->setUser($user);
            $this->managerRegistry->getManager()->persist($projectLock);
            $this->managerRegistry->getManager()->flush();
        } else {
            $projectLock = (
                isset($project->getProjectLocks()[0])
                && $project->getProjectLocks()[0] instanceof ProjectLock
            )
                ? $project->getProjectLocks()[0]
                : null;
            // on met à jour le lock si le user et l'aide sont bien les mêmes
            if ($projectLock && $projectLock->getUser() == $user && $projectLock->getProject() == $project) {
                $projectLock->setTimeStart(new \DateTime(date('Y-m-d H:i:s')));
                $projectLock->setProject($project);
                $projectLock->setUser($user);
                $this->managerRegistry->getManager()->persist($projectLock);
                $this->managerRegistry->getManager()->flush();
            }
        }
    }

    public function unlock(Project $project): void
    {
        foreach ($project->getProjectLocks() as $projectLock) {
            $this->managerRegistry->getManager()->remove($projectLock);
        }
        $this->managerRegistry->getManager()->flush();
    }

    public function getProjectAidsExportPdfContent(Project $project): string
    {
        $pdfOptions = new Options();
        $pdfOptions->setIsRemoteEnabled(true);

        // instantiate and use the dompdf class
        $dompdf = new Dompdf($pdfOptions);

        $dompdf->loadHtml(
            $this->twig->render('user/project/aids_list_export_pdf.html.twig', [
                'project' => $project,
                'organization' => ($project->getAuthor() && $project->getAuthor()->getDefaultOrganization())
                    ? $project->getAuthor()->getDefaultOrganization()
                    : null,
                'today' => new \DateTime(date('Y-m-d H:i:s'))
            ])
        );

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();


        return $dompdf->output();
    }
}
