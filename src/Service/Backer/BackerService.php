<?php

namespace App\Service\Backer;

use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerLock;
use App\Entity\Organization\OrganizationAccess;
use App\Entity\User\User;
use App\Service\Organization\OrganizationService;
use Doctrine\Persistence\ManagerRegistry;

class BackerService
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected OrganizationService $organizationService
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

    public function canUserLock(Backer $backer, User $user): bool
    {
        if (!$backer->getOrganizations()) {
            return false;
        }

        foreach ($backer->getOrganizations() as $organization) {
            if ($this->organizationService->canEditBacker($user, $organization)) {
                return true;
            }
        }

        return false;
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
            if ($backerLock->getTimeStart() < $now->sub(new \DateInterval('PT'.$minutesMax.'M'))) {
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
        return count($backer->getBackerLocks()) > 0;
    }
    
    public function lock(Backer $backer, User $user): void
    {
        try {
            if (count($backer->getBackerLocks()) == 0) {
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
        } catch (\Exception $e) {
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