<?php

namespace App\Controller\Page;

use App\Controller\FrontController;
use App\Entity\Page\Page;
use App\Exception\NotFoundException\PageNotFoundException;
use App\Repository\Page\PageRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority: 0)]
class PageController extends FrontController
{
    #[Route('/{url}/', name: 'app_page_custom')]
    public function index(
        string $url,
        PageRepository $pageRepository
    ): Response {

        $page = $pageRepository->findOneBy(
            [
                'url' => $url,
            ]
        );
        if (!$page instanceof Page) {
            // si pas de / en début et fin de $url, on essaye en les rajoutant
            if (substr($url, 0, 1) !== '/' && substr($url, -1) !== '/') {
                $url = '/' . $url . '/';
                $page = $pageRepository->findOneBy(
                    [
                        'url' => $url,
                    ]
                );
                if (!$page instanceof Page) {
                    throw new PageNotFoundException('Cette page n\'existe pas.');
                }
            }
        }

        // fil arianne
        $this->breadcrumb->add(
            $page->getMetaTitle() ?? $page->getName(),
        );

        return $this->render('page/page/index.html.twig', [
            'page' => $page
        ]);
    }
}
