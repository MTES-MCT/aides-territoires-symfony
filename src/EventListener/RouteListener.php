<?php

namespace App\EventListener;

use App\Controller\Page\PageController;
use App\Entity\Page\Page;
use App\Repository\Page\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class RouteListener
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private PageRepository $pageRepository
    )
    {
        
    }
    public function onKernelRequest(
        RequestEvent $event
    ): void
    {
        // url demandée
        $url = urldecode($event->getRequest()->getRequestUri()) ?? null;

        // regarde si une page corresponds
        $page = $this->pageRepository->findOneBy(
            [
                'url' => $url,
            ]
        );

        // si page trouvée
        if ($page instanceof Page) {
            $event->getRequest()->attributes->set('url', $url);
            $event->getRequest()->attributes->set('_route_params', ['url' => $url]);
            $event->getRequest()->attributes->set(
                '_controller',
                PageController::class . '::index'
            );
            ;
        }
    }
}