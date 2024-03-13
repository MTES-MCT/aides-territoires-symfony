<?php

namespace App\Service\Backer;

use App\Entity\Backer\Backer;
use App\Entity\User\User;

class BackerService
{
    public function userCanSee(?User $user, ?Backer $backer) {
        if (!$user instanceof User || !$backer instanceof Backer) {
            return false;
        }

        $return = false;
        foreach ($backer->getBackerUsers() as $backerUser) {
            if ($backerUser->getUser() === $user) {
                $return = true;
                break;
            }
        }

        return $return;
    }

    public function userCanEdit(?User $user, ?Backer $backer) {
        if (!$user instanceof User || !$backer instanceof Backer) {
            return false;
        }

        $return = false;
        foreach ($backer->getBackerUsers() as $backerUser) {
            if ($backerUser->getUser() === $user && ($backerUser->isEditor() || $backerUser->isAdministrator())) {
                $return = true;
                break;
            }
        }

        return $return;
    }

    public function userCanAdmin(?User $user, ?Backer $backer) {
        if (!$user instanceof User || !$backer instanceof Backer) {
            return false;
        }

        $return = false;
        foreach ($backer->getBackerUsers() as $backerUser) {
            if ($backerUser->getUser() === $user && $backerUser->isAdministrator()) {
                $return = true;
                break;
            }
        }

        return $return;
    }
}