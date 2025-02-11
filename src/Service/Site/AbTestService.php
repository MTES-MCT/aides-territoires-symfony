<?php

namespace App\Service\Site;

use App\Entity\Site\AbTest;
use App\Entity\Site\AbTestUser;
use App\Exception\BusinessException\Site\AbTestException;
use App\Repository\Site\AbTestRepository;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class AbTestService
{
    public const VAPP_ACTIVATION = 'vapp_activation';
    public const VAPP_FORMULAIRE = 'vapp_formulaire';

    public function __construct(
        private RequestStack $requestStack,
        private ManagerRegistry $managerRegistry,
        private AbTestRepository $abTestRepository,
        private UserService $userService,
        private CookieService $cookieService
    ) {
    }

    public function shouldShowTestVersion(string $abTestName): bool
    {
        try {
            $abTest = $this->abTestRepository->findOneBy(['name' => $abTestName]);
            if (!$abTest) {
                throw new AbTestException('A/B test not found');
            }
    
            // Vérifie d'abord si un cookie existe
            $cookieName = 'abtest_' . $abTestName;
            if ($this->requestStack->getCurrentRequest()->cookies->has($cookieName)) {
                return $this->requestStack->getCurrentRequest()->cookies->get($cookieName) === 'true';
            }
    
            // réparti au hasard
            $userInTest = $this->isUserInTest($abTest);
    
            // Créer un cookie qui expire dans 30 jours
            $this->cookieService->setCookie($cookieName, $userInTest ? 'true' : 'false');
    
            if ($abTest instanceof AbTest) {
                $abTestUser = new AbTestUser();
                $abTestUser->setAbTest($abTest);
                $abTestUser->setVariation($userInTest ? 1 : 0);
                $abTestUser->setUser($this->userService->getUserLogged());
    
                $this->managerRegistry->getManager()->persist($abTestUser);
                $this->managerRegistry->getManager()->flush();
            }

            return $userInTest;
        } catch (\Exception $e) {
            return false;
        }
    }

    // fonction pour déterminer si le user va faire partir du test à partir du ratio
    private function isUserInTest(AbTest $abTest): bool
    {
        return true;
        return (random_int(1, 100) / 100) <= $abTest->getRatio();
    }

    // Stocke la participation ou non du user au test dans un cookie
    private function setCookie(string $name, string $value, int $expire = 0): void
    {
        $cookie = Cookie::create('foo')
        ->withValue('bar')
        ->withExpires(strtotime('Fri, 20-May-2011 15:25:52 GMT'))
        ->withDomain('.example.com')
        ->withSecure(true);
    }

    // Méthode pour récupérer les cookies dans le contrôleur
    public function applyCookiesToResponse(Response $response): void
    {
        $request = $this->requestStack->getCurrentRequest();
        foreach ($request->attributes->keys() as $key) {
            if (str_starts_with($key, '_abc_cookie_')) {
                $cookie = $request->attributes->get($key);
                if ($cookie instanceof Cookie) {
                    $response->headers->setCookie($cookie);
                }
            }
        }
    }
}
