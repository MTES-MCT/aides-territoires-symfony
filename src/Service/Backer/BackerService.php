<?php

namespace App\Service\Backer;

use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerLock;
use App\Entity\User\User;
use Doctrine\Persistence\ManagerRegistry;

class BackerService
{
    public function __construct(
        private ManagerRegistry $managerRegistry
    ) {
    }

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

    public function canUserLock(Backer $backer, User $user): bool
    {
        return $this->userCanEdit($user, $backer);
    }

    public function getLock(Backer $backer): ?BackerLock
    {
        foreach ($backer->getBackerLocks() as $backerLock) {
            return $backerLock;
        }

        return null;
    }
    public function isLockedByAnother(Backer $backer, User $user): bool
    {
        $now = new \DateTime(date('Y-m-d H:i:s'));
        $minutesMax = 5;
        foreach ($backer->getBackerLocks() as $backerLock) {
            // si le lock a plus de 5 min, on le supprime
            if ($backerLock->getTimeStart() < $now->sub(new \DateInterval('PT' . $minutesMax . 'M'))) {
                $this->managerRegistry->getManager()->remove($backerLock);
                $this->managerRegistry->getManager()->flush();
                continue;
            }

            if ($backerLock->getUser() != $user) {
                return true;
            }
        }
        return false;
    }

    public function isLocked(Backer $backer): bool
    {
        return !$backer->getBackerLocks()->isEmpty();
    }

    public function lock(Backer $backer, User $user): void
    {
        if ($backer->getBackerLocks()->isEmpty()) {
            $backerLock = new BackerLock();
            $backerLock->setBacker($backer);
            $backerLock->setUser($user);
            $this->managerRegistry->getManager()->persist($backerLock);
            $this->managerRegistry->getManager()->flush();
        } else {
            $backerLock = (isset($backer->getBackerLocks()[0]) && $backer->getBackerLocks()[0] instanceof BackerLock)
                ? $backer->getBackerLocks()[0]
                : null;
            // on met à jour le lock si le user et l'aide sont bien les mêmes
            if ($backerLock && $backerLock->getUser() == $user && $backerLock->getBacker() == $backer) {
                $backerLock->setTimeStart(new \DateTime(date('Y-m-d H:i:s')));
                $backerLock->setBacker($backer);
                $backerLock->setUser($user);
                $this->managerRegistry->getManager()->persist($backerLock);
                $this->managerRegistry->getManager()->flush();
            }
        }
    }

    public function unlock(Backer $backer): void
    {
        foreach ($backer->getBackerLocks() as $backerLock) {
            $this->managerRegistry->getManager()->remove($backerLock);
        }
        $this->managerRegistry->getManager()->flush();
    }
}
