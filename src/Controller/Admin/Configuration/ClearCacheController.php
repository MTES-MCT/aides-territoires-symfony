<?php

namespace App\Controller\Admin\Configuration;

use App\Service\File\FileService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ClearCacheController extends AbstractController
{
    public function __construct(
        private KernelInterface $kernelInterface,
    ) {
    }

    #[Route(
        '/admin/confifuration/clear-cache',
        name: 'admin_configuration_clear_cache'
    )]
    public function clearCache(): Response
    {
        // gestion du cache symfony
        try {
            $env = $this->kernelInterface->getEnvironment();

            $command = ['php', 'bin/console', 'cache:clear'];
            if ($env == FileService::ENV_PROD) {
                $command[] = '--env=prod';
            }

            $process = new Process($command);
            $process->setWorkingDirectory($this->getParameter('kernel.project_dir'));
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $this->addFlash('success', 'Cache vidé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du vidage du cache: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin');
    }
}
