<?php

namespace App\Controller\Admin\Log;

use App\Controller\Admin\DashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class LogSymfonyController extends DashboardController
{
    #[Route('/admin/log/symfony/download', name: 'admin_log_symfony_download')]
    public function download(
        KernelInterface $kernelInterface
    ): Response {
        $logsDir = $kernelInterface->getLogDir();
        $logFilename = $kernelInterface->getEnvironment() . '.log';
        $logFile = $logsDir . '/' . $logFilename;
        if (is_file($logFile)) {
            try {
                $fileContent = file_get_contents($logFile);
                $response = new Response($fileContent, 200, ['Content-Type' => 'text/plain']);
                $response->headers->set('Content-Disposition', 'attachment; filename="' . $logFilename . '"');
                return $response;
            } catch (\Exception $e) {
                $file = null;
            }
        } else {
            $file = null;
        }

        // rendu template
        return $this->render('admin/log/symfony/download.html.twig', [
            'file' => $file
        ]);
    }
}
