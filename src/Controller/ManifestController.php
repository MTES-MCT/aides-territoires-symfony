<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ManifestController extends FrontController
{
    #[Route('/manifest.webmanifest', name: 'app_manifest_favicon', methods: ['GET'])]
    public function faviconManifest(): Response
    {
        $response = $this->render('manifest/favicon-manifest.json.twig');
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    #[Route('/manifest.json', name: 'app_manifest', methods: ['GET'])]
    public function manifest(): Response
    {
        return $this->faviconManifest();
    }
}
