<?php

namespace App\EventListener;

use App\Entity\Program\Program;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Repository\Search\SearchPageRepository;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class SubDomainListener
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private SearchPageRepository $searchPageRepository,
        private RouterInterface $routerInterface,
        private ParamService $paramService,
        private NotificationService $notificationService,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // Si ce n'est pas la requête principale, ne fait rien
        if (!$event->isMainRequest()) {
            return;
        }

        // ----------------------------------------------------------------------------------
        // Si sous domaine, on va regarder si cela corresponds à une SearchPage (portail)
        $host = $event->getRequest()->getHost();

        // spe aides.francemobilites.fr
        if ('aides.francemobilites.fr' == $host) {
            $host = $this->paramService->get('prod_host');
            $context = $this->routerInterface->getContext();
            $context->setHost($host);
            $context->setScheme('https');
            $url = $this->routerInterface->generate(
                'app_portal_portal_details',
                ['slug' => 'francemobilites'],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $response = new RedirectResponse($url);
            $event->setResponse($response);

            return;
        }

        // Sépare le nom de l'hôte en ses composants
        $hostParts = explode('.', $host);

        // Le sous-domaine est le premier composant
        $subdomain = $hostParts[0] ?? null;
        $this->handleSubdomain($event, $subdomain);
    }

    private function handleSubdomain(RequestEvent $event, ?string $subdomain): void
    {
        if ($subdomain) {
            // pour s'assurer de rediriger vers la prod
            $host = $this->paramService->get('prod_host');
            $context = $this->routerInterface->getContext();
            $context->setHost($host);
            $context->setScheme('https');

            // spe life-europe.aides-territoires.beta.gouv.fr :
            if ('life-europe' == $subdomain) {
                $program = $this->entityManagerInterface->getRepository(Program::class)->findOneBy(['slug' => 'life']);
                if ($program instanceof Program) {
                    $url = $this->routerInterface->generate(
                        'app_program_details',
                        ['slug' => $program->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);

                    return;
                }
            } elseif ('biodiversite-occitanie' == $subdomain) { // spe biodiversite occitanie
                $url = $this->routerInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
                $response = new RedirectResponse($url);
                $event->setResponse($response);

                return;
            }

            // on regarde si cela corresponds à un portail
            $searchPage = $this->searchPageRepository->findOneBy(
                [
                    'slug' => $subdomain,
                ]
            );

            if ($searchPage instanceof SearchPage) {
                try {
                    // redirige vers le portail
                    $url = $this->routerInterface->generate(
                        'app_portal_portal_details',
                        ['slug' => $subdomain],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);

                    return;
                } catch (\Exception $e) {
                    $admin = $this->entityManagerInterface->getRepository(User::class)
                        ->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
                    $this->notificationService->addNotification(
                        $admin,
                        'Erreur redirection portail',
                        'Portail : ' . $searchPage->getName()
                    );
                }
            } else {
                if ($host != $this->paramService->get('prod_host')) {
                    // // // portail non existant, on redirige vers la page d'accueil
                    $url = $this->routerInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }
            }
        }
    }
}
