<?php

namespace App\Service\Backer;

use App\Entity\Backer\Backer;
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

        // si membre de l'organization
        foreach ($backer->getOrganizations() as $organization) {
            if ($organization->getBeneficiairies()->contains($user)) {
                return true;
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
            if ($organization->getBeneficiairies()->contains($user)) {
                return true;
            }
        }
        return false;
    }
}