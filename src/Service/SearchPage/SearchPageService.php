<?php

namespace App\Service\SearchPage;

use App\Entity\Search\SearchPage;
use App\Entity\Search\SearchPageLock;
use App\Entity\User\User;
use App\Service\Organization\OrganizationService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;

class SearchPageService
{
    public function __construct(
        private UserService $userService,
        private OrganizationService $organizationService,
        private ManagerRegistry $managerRegistry
    )
    {
    }

    public function userCanViewEdit(SearchPage $searchPage, User $user)
    {
        // si c'est l'auteur ou un admin
        if ($searchPage->getAdministrator() == $user || $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
            return true;
        }

        // si il appartiens à l'organisation
        if ($searchPage->getOrganization()) {
            foreach ($searchPage->getOrganization()->getOrganizationAccesses() as $organizationAccess) {
                if ($organizationAccess->getUser() == $user) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canUserLock(SearchPage $searchPage, User $user): bool
    {
        if (!$searchPage->getOrganization()) {
            return false;
        }

        if ($this->organizationService->canEditPortal($user, $searchPage->getOrganization())) {
            return true;
        }

        return false;
    }
    
    public function getLock(SearchPage $searchPage): ?SearchPageLock
    {
        foreach ($searchPage->getSearchPageLocks() as $searchPageLock) {
            return $searchPageLock;
        }

        return null;
    }
    public function isLockedByAnother(SearchPage $searchPage, User $user): bool
    {
        foreach ($searchPage->getSearchPageLocks() as $searchPageLock) {
            if ($searchPageLock->getUser() != $user) {
                return true;
            }
        }
        return false;
    }

    public function isLocked(SearchPage $searchPage): bool
    {
        return count($searchPage->getSearchPageLocks()) > 0;
    }
    
    public function lock(SearchPage $searchPage, User $user): void
    {
        try {
            // vérifie que l'aide n'est pas déjà lock
            if (count($searchPage->getSearchPageLocks()) == 0) {
                $searchPageLock = new SearchPageLock();
                $searchPageLock->setSearchPage($searchPage);
                $searchPageLock->setUser($user);
                $this->managerRegistry->getManager()->persist($searchPageLock);
                $this->managerRegistry->getManager()->flush();
            }
        } catch (\Exception $e) {
        }
    }

    public function unlock(SearchPage $searchPage): void
    {
        foreach ($searchPage->getSearchPageLocks() as $searchPageLock) {
            $this->managerRegistry->getManager()->remove($searchPageLock);
        }
        $this->managerRegistry->getManager()->flush();
    }
}