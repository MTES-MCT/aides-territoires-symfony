<?php

namespace App\Service\Organization;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationAccess;
use App\Entity\User\User;

class OrganizationService
{
    public function canEdit(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getBeneficiairies()->contains($user);
    }

    public function canViewEdit(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getBeneficiairies()->contains($user);
    }

    public function userAdminOf(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($user) {
            return $organizationAccess->getUser() === $user && $organizationAccess->isAdministrator();
        });
    }
}