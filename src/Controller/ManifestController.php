<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;

class ManifestController extends FrontController
{
    #[Route('/manifest.webmanifest', name: 'app_manifest_favicon', methods: ['GET'])]
    public function faviconManifest(

    ) {
        $response = $this->render('manifest/favicon-manifest.json.twig');
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}