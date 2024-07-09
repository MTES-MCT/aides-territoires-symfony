<?php

namespace App\Controller;

use App\Service\File\FileService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(priority:1)]
class RobotsTxtController extends FrontController
{
    #[Route('/robots.txt', name: 'app_static_robots')]
    public function index(FileService $fileService) : Response {

        $content = $this->renderView('robots.html.twig', ['env' => $fileService->getEnvironment()]);

        $response = new Response($content, Response::HTTP_OK, ['content-type' => 'text/plain']);

        return $response;
    }
}