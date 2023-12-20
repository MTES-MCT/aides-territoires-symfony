<?php

namespace App\Controller\Page;

use App\Controller\FrontController;
use App\Entity\Page\Page;
use App\Repository\Page\PageRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority:0)]
class PageController extends FrontController
{
    #[Route('/{url}/', name: 'app_page_custom')]
    public function index(
        $url,
        PageRepository $pageRepository
        ): Response
    {

        $page = $pageRepository->findOneBy(
            [
                'url' => $url,
            ]
        );
        if (!$page instanceof Page) {
            throw $this->createNotFoundException('Cette page n\'existe pas.');
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
