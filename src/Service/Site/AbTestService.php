<?php

namespace App\Service\Site;

use App\Entity\Site\AbTest;
use App\Entity\Site\AbTestUser;
use App\Exception\BusinessException\Site\AbTestException;
use App\Repository\Site\AbTestRepository;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class AbTestService
{
    public const VAPP_ACTIVATION = 'vapp_activation';
    public const VAPP_FORMULAIRE = 'vapp_formulaire';

    public function __construct(
        private RequestStack $requestStack,
        private ManagerRegistry $managerRegistry,
        private AbTestRepository $abTestRepository,
        private UserService $userService,
        private CookieService $cookieService,
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
                return 'true' === $this->requestStack->getCurrentRequest()->cookies->get($cookieName);
            }

            // réparti au hasard
            $userInTest = $this->isUserInTest($abTest);

            // Créer un cookie
            $this->cookieService->setCookie($cookieName, $userInTest ? 'true' : 'false');

            if ($abTest instanceof AbTest) {
                // on regarde si on enregistre le user dans la table
                $abTestUser = null;
                $userCookieID = $this->requestStack->getCurrentRequest()->cookies->get($cookieName . '_userId', null);

                // si pas des les cookies on vérifie dans la session
                if (!$userCookieID) {
                    $userCookieID = $this->requestStack->getCurrentRequest()->getSession()->get(
                        $cookieName . '_userId',
                        null
                    );
                }
                if ($userCookieID) {
                    $abTestUser = $this->managerRegistry->getRepository(AbTestUser::class)->findOneBy([
                        'abTest' => $abTest,
                        'cookieId' => $userCookieID,
                    ]);
                }
                if (!$abTestUser) {
                    $abTestUser = new AbTestUser();
                    $abTestUser->setAbTest($abTest);
                    $abTestUser->setVariation($userInTest ? '1' : '0');
                    $abTestUser->setUser($this->userService->getUserLogged());
                    $abTestUser->setCookieId(bin2hex(random_bytes(16)));

                    // on stocke l'id dans les cookies
                    $this->cookieService->setCookie($cookieName . '_userId', $abTestUser->getCookieId());
                    // on stocke également l'id dans la session pour éviter les doublons
                    $this->requestStack->getCurrentRequest()->getSession()->set(
                        $cookieName . '_userId',
                        $abTestUser->getCookieId()
                    );

                    // sauvegarde en base
                    $this->managerRegistry->getManager()->persist($abTestUser);
                    $this->managerRegistry->getManager()->flush();
                }
            }

            return $userInTest;
        } catch (\Exception $e) {
            return false;
        }
    }

    // fonction pour déterminer si le user va faire partir du test à partir du ratio
    private function isUserInTest(AbTest $abTest): bool
    {
        return (random_int(1, 100) / 100) <= $abTest->getRatio();
    }
}
