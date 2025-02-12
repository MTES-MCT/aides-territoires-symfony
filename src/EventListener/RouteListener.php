<?php

namespace App\EventListener;

use App\Controller\Page\PageController;
use App\Entity\Log\LogUrlRedirect;
use App\Entity\Page\Page;
use App\Entity\Site\UrlRedirect;
use App\Entity\User\User;
use App\Repository\Page\PageRepository;
use App\Repository\Search\SearchPageRepository;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

final class RouteListener
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private PageRepository $pageRepository,
        private SearchPageRepository $searchPageRepository,
        private RouterInterface $routerInterface,
        private ParamService $paramService,
        private Packages $packages,
        private NotificationService $notificationService,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // Vérifier si c'est une 404
        if (!($event->getThrowable() instanceof NotFoundHttpException)) {
            return;
        }

        // Si ce n'est pas la requête principale, ne fait rien
        if (!$event->isMainRequest()) {
            return;
        }

        // 404 static Django
        $this->handleDjangoStatic($event);

        // url de redirections
        $this->handleRedirect($event);

        // regarde si une page corresponds à l'url demandée
        $this->handlePage($event);
    }

    private function handleRedirect(RequestEvent $event): void
    {
        // url demandée
        $url = urldecode($event->getRequest()->getRequestUri());

        $urlRedirectRepository = $this->entityManagerInterface->getRepository(UrlRedirect::class);

        // regarde si une url de redirection corresponds à l'url demandée
        $urlRedirect = $urlRedirectRepository->findOneBy(
            [
                'oldUrl' => $url,
            ]
        );

        // si url de redirection trouvée
        if ($urlRedirect instanceof UrlRedirect) {
            try {
                // log du redirect
                $logUrlRedirect = new LogUrlRedirect();
                $logUrlRedirect->setUrlRedirect($urlRedirect);
                $logUrlRedirect->setIp($event->getRequest()->getClientIp() ?? null);
                $logUrlRedirect->setReferer($event->getRequest()->headers->get('referer') ?? null);
                $logUrlRedirect->setUserAgent($event->getRequest()->headers->get('user-agent') ?? null);
                $logUrlRedirect->setRequestUri(
                    !empty($event->getRequest()->getRequestUri())
                        ? $event->getRequest()->getRequestUri()
                        : null
                );
                $this->entityManagerInterface->persist($logUrlRedirect);
                $this->entityManagerInterface->flush();

                // redirige vers la nouvelle url
                $response = new RedirectResponse($urlRedirect->getNewUrl());
                $event->setResponse($response);

                return;
            } catch (\Exception $e) {
                $admin = $this->entityManagerInterface->getRepository(User::class)
                    ->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
                $this->notificationService->addNotification($admin, 'Erreur redirection url', 'Url : '.$url);
            }
        }
    }

    private function handlePage(RequestEvent $event): void
    {
        // url demandée
        $url = urldecode($event->getRequest()->getRequestUri());

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
                    PageController::class.'::index'
                );
            } catch (\Exception $e) {
                $admin = $this->entityManagerInterface->getRepository(User::class)
                    ->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
                $this->notificationService->addNotification($admin, 'Erreur redirection page', 'Page : '.$url);
            }
        }
    }

    private function handleDjangoStatic(RequestEvent $event): void
    {
        $url = urldecode($event->getRequest()->getRequestUri());

        $newFaviconSvg = 'build/images/favicon/favicon.svg';
        $known404 = [
            '/static/img/logo_AT_og.png' => $this->packages->getUrl('build/images/logo/logo_AT_og.png'),
            '/app/public/static/img/logo_AT_og.png' => $this->packages->getUrl('build/images/logo/logo_AT_og.png'),
            '/static/favicons/favicon.93b88edf055e.svg' => $this->packages->getUrl($newFaviconSvg),
            '/app/public/static/favicons/favicon.93b88edf055e.svg' => $this->packages->getUrl($newFaviconSvg),
            '/static/favicons/favicon-32x32.05f90bae01cd.png' => $this->packages->getUrl('build/images/favicon/favicon.svg'),
            '/app/public/static/favicons/favicon-32x32.05f90bae01cd.png' => $this->packages->getUrl('build/images/favicon/favicon.svg'),
            '/static/favicons/favicon.12acb9fc12ee.ico' => $this->packages->getUrl('build/images/favicon/favicon.ico'),
            '/app/public/static/favicons/favicon.12acb9fc12ee.ico' => $this->packages->getUrl('build/images/favicon/favicon.ico'),
            '/static/favicons/favicon.05f90bae01cd.png' => $this->packages->getUrl($newFaviconSvg),
            '/app/public/static/favicons/favicon.05f90bae01cd.png' => $this->packages->getUrl($newFaviconSvg),
            '/favicon.ico' => $this->packages->getUrl('build/images/favicon/favicon.ico'),
            '/app/public/favicon.ico' => $this->packages->getUrl('build/images/favicon/favicon.ico'),
            '/favicon.svg' => $this->packages->getUrl($newFaviconSvg),
            '/apple-touch-icon.png' => $this->packages->getUrl('build/images/favicon/apple-touch-icon.png'),
            '/recherche/trouver-des-aides' => $this->routerInterface->generate('app_aid_aid'),
            '/recherche/trouver-des-aides/' => $this->routerInterface->generate('app_aid_aid'),
            '/notifications/' => $this->routerInterface->generate('app_user_user_notification'),
            '/api/schema' => '/api/docs.jsonld',
            '/api/schema/' => '/api/docs.jsonld',
        ];

        if (isset($known404[$url])) {
            try {
                $response = new RedirectResponse($known404[$url]);
                $event->setResponse($response);

                return;
            } catch (\Exception $e) {
                $admin = $this->entityManagerInterface->getRepository(User::class)
                    ->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
                $this->notificationService->addNotification(
                    $admin,
                    'Erreur redirection static django',
                    'Url : '.$url
                );
            }
        }
    }
}
