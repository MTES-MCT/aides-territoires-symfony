<?php

namespace App\Service\Organization;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationAccess;
use App\Entity\User\User;
use App\Service\Notification\NotificationService;

class OrganizationService
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    public function hasOneAdminAtLeast(Organization $organization): bool
    {
        foreach ($organization->getOrganizationAccesses() as $organizationAccess) {
            if ($organizationAccess->isAdministrator()) {
                return true;
            }
        }

        return false;
    }
    
    public function canViewEdit(?User $user, ?Organization $organization): bool
    {
        return $this->userMemberOf($user, $organization);
    }

    public function canEditAid(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($user) {
            return $organizationAccess->getUser() === $user && ($organizationAccess->isEditAid() || $organizationAccess->isAdministrator());
        });
    }

    public function canEditPortal(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($user) {
            return $organizationAccess->getUser() === $user && ($organizationAccess->isEditPortal() || $organizationAccess->isAdministrator());
        });
    }

    public function canEditBacker(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($user) {
            return $organizationAccess->getUser() === $user && ($organizationAccess->isEditBacker() || $organizationAccess->isAdministrator());
        });
    }

    public function canEditProject(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($user) {
            return $organizationAccess->getUser() === $user && ($organizationAccess->isEditProject() || $organizationAccess->isAdministrator());
        });
    }

    public function emailMemberOf(?string $email, ?Organization $organization): bool
    {
        if (!$email || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($email) {
            return $organizationAccess->getUser()->getEmail() === $email;
        });
    }

    public function userMemberOf(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($user) {
            return $organizationAccess->getUser() === $user;
        });
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

    public function sendNotificationToMembers(
        Organization $organization,
        string $title,
        string $message,
        ?array $exludeUsers = []
    ) {
        foreach ($organization->getOrganizationAccesses() as $organizationAccess) {
            if ($organizationAccess->getUser()) {
                foreach ($exludeUsers as $excludeUser) {
                    if ($organizationAccess->getUser()->getId() === $excludeUser->getId()) {
                        continue 2;
                    }
                }
                $this->notificationService->addNotification(
                    $organizationAccess->getUser(),
                    $title,
                    $message
                );
            }
        }
    }
}