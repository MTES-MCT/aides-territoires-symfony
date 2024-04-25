<?php

namespace App\Service\SearchPage;

use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Service\User\UserService;

class SearchPageService
{
    public function __construct(
        private UserService $userService
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
}