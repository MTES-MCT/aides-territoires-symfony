<?php

namespace App\Service\Project;

use App\Repository\Project\ProjectRepository;

class ProjectService
{
    public function __construct(
        protected ProjectRepository $projectRepository
    )
    {
    }

    public function searchProjects(?array $projectParams = null)
    {
        $projects = $this->projectRepository->findCustom($projectParams);
        return $projects;
    }
}