<?php

namespace App\EventListener;

use App\Controller\Page\PageController;
use App\Controller\Portal\PortalController;
use App\Entity\Page\Page;
use App\Entity\Search\SearchPage;
use App\Repository\Page\PageRepository;
use App\Repository\Search\SearchPageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class RouteListener
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private PageRepository $pageRepository,
        private SearchPageRepository $searchPageRepository,
        private RouterInterface $routerInterface,
        private KernelInterface $kernelInterface
    )
    {
        
    }
    public function onKernelRequest(
        RequestEvent $event
    ): void
    {
        // Si ce n'est pas la requête principale, ne fait rien
        if (!$event->isMainRequest()) {
            return;
        }

        //----------------------------------------------------------------------------------
        // Si sous domaine, on va regarder si cela corresponds à une SearchPage (portail)
        $host = $event->getRequest()->getHost();

        // Sépare le nom de l'hôte en ses composants
        $hostParts = explode('.', $host);

        // Le sous-domaine est le premier composant
        $subdomain = $hostParts[0] ?? null;
            if ($subdomain) {
            // on regarde si cela corresponds à un portail
            $searchPage = $this->searchPageRepository->findOneBy(
                [
                    'slug' => $subdomain,
                ]
            );
            if ($searchPage instanceof SearchPage) {
                try {
                    $url = $this->routerInterface->generate('app_portal_portal_details', ['slug' => $subdomain], UrlGeneratorInterface::ABSOLUTE_URL);
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                } catch (\Exception $e) {

                }
            }
        }

        //----------------------------------------------------------------------------------
        // regarde si une page corresponds à l'url demandée
        
        // url demandée
        $url = urldecode($event->getRequest()->getRequestUri()) ?? null;
        
        $page = $this->pageRepository->findOneBy(
            [
                'url' => $url,
            ]
        );

        // si page trouvée
        if ($page instanceof Page) {
            try {
                $event->getRequest()->attributes->set('url', $url);
                $event->getRequest()->attributes->set('_route_params', ['url' => $url]);
                $event->getRequest()->attributes->set(
                    '_controller',
                    PageController::class . '::index'
                );
            } catch (\Exception $e) {

            }
        }
    }
}