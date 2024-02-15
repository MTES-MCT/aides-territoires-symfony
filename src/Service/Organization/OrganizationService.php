<?php

namespace App\Service\Organization;

use App\Entity\Organization\Organization;
use App\Entity\User\User;

class OrganizationService
{
    public function canEdit(?User $user, ?Organization $organization): bool
    {
        if ($user === null || $organization === null) {
            return false;
        }

        return $organization->getBeneficiairies()->contains($user);
    }
}