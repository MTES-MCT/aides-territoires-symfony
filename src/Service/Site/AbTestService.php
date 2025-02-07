<?php

namespace App\Service\Site;

use App\Entity\Site\AbTest;
use App\Entity\Site\AbTestUser;
use App\Repository\Site\AbTestRepository;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class AbTestService
{
    private const SEARCH_FORM_TEST = 'search_form_test';
    private const TEST_RATIO = 0.1; // 10% du trafic

    public function __construct(
        private RequestStack $requestStack,
        private ManagerRegistry $managerRegistry,
        private AbTestRepository $abTestRepository,
        private UserService $userService
    ) {
    }

    public function shouldShowTestVersion(): bool
    {
        $session = $this->requestStack->getSession();

        // Si déjà défini en session, on garde la même version
        if ($session->has(self::SEARCH_FORM_TEST)) {
            return $session->get(self::SEARCH_FORM_TEST);
        }

        // Sinon on assigne une version selon le ratio
        $isTest = (random_int(1, 100) / 100) <= self::TEST_RATIO;
        $session->set(self::SEARCH_FORM_TEST, $isTest);

        $abTest = $this->abTestRepository->findOneBy(['name' => self::SEARCH_FORM_TEST]);
        if ($abTest instanceof AbTest) {
            $abTestUser = new AbTestUser();
            $abTestUser->setAbTest($abTest);
            $abTestUser->setVersion($isTest ? 'vapp' : 'at');
            $abTestUser->setUser($this->userService->getUserLogged());
        }
        
        return $isTest;
    }
}
