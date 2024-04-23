<?php

namespace App\Service\Backer;

use App\Entity\Backer\Backer;
use App\Entity\Organization\OrganizationAccess;
use App\Entity\User\User;

class BackerService
{
    public function userCanPreview(?User $user, ?Backer $backer): bool
    {
        if (!$user instanceof User || !$backer instanceof Backer) {
            return false;
        }

        // si admin
        foreach ($user->getRoles() as $role) {
            if ($role == User::ROLE_ADMIN) {
                return true;
            }
        }

        // si membre de l'organization et à les droits
        foreach ($backer->getOrganizations() as $organization) {
            foreach ($organization->getOrganizationAccesses() as $organizationAccess) {
                if ($organizationAccess->getUser()->getId() == $user->getId()) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function userCanEdit(?User $user, ?Backer $backer): bool
    {
        if (!$user instanceof User || !$backer instanceof Backer) {
            return false;
        }

        foreach ($backer->getOrganizations() as $organization) {
            /** @var OrganizationAccess $organizationAccess */
            foreach ($organization->getOrganizationAccesses() as $organizationAccess) {
                if ($organizationAccess->getUser()->getId() == $user->getId() && $organizationAccess->isEditBacker()) {
                    return true;
                }
            }
        }
        return false;
    }
}