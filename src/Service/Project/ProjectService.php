<?php

namespace App\Service\Project;

use App\Entity\Project\Project;
use App\Entity\Project\ProjectLock;
use App\Entity\User\User;
use App\Repository\Project\ProjectRepository;
use App\Service\Organization\OrganizationService;
use Doctrine\Persistence\ManagerRegistry;

class ProjectService
{
    public function __construct(
        protected ProjectRepository $projectRepository,
        protected OrganizationService $organizationService,
        protected ManagerRegistry $managerRegistry
    )
    {
    }

    public function searchProjects(?array $projectParams = null)
    {
        $projects = $this->projectRepository->findCustom($projectParams);
        return $projects;
    }

    public function canUserLock(Project $project, User $user): bool
    {
        if (!$project->getOrganization()) {
            return false;
        }

        if ($this->organizationService->canEditProject($user, $project->getOrganization())) {
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
        foreach ($project->getProjectLocks() as $projectLock) {
            if ($projectLock->getUser() != $user) {
                return true;
            }
        }
        return false;
    }

    public function isLocked(Project $project): bool
    {
        return count($project->getProjectLocks()) > 0;
    }
    
    public function lock(Project $project, User $user): void
    {
        try {
            if (count($project->getProjectLocks()) == 0) {
                $projectLock = new ProjectLock();
                $projectLock->setProject($project);
                $projectLock->setUser($user);
                $this->managerRegistry->getManager()->persist($projectLock);
                $this->managerRegistry->getManager()->flush();
            }
        } catch (\Exception $e) {
        }
    }

    public function unlock(Project $project): void
    {
        foreach ($project->getProjectLocks() as $projectLock) {
            $this->managerRegistry->getManager()->remove($projectLock);
        }
        $this->managerRegistry->getManager()->flush();
    }
}