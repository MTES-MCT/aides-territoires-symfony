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

    public function canEdit(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getBeneficiairies()->contains($user);
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
            return $organizationAccess->getUser() === $user && $organizationAccess->isEditAid();
        });
    }

    public function canEditPortal(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($user) {
            return $organizationAccess->getUser() === $user && $organizationAccess->isEditPortal();
        });
    }

    public function canEditBacker(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($user) {
            return $organizationAccess->getUser() === $user && $organizationAccess->isEditBacker();
        });
    }

    public function canEditProject(?User $user, ?Organization $organization): bool
    {
        if (!$user instanceof User || !$organization instanceof Organization) {
            return false;
        }

        return $organization->getOrganizationAccesses()->exists(function (int $key, OrganizationAccess $organizationAccess) use ($user) {
            return $organizationAccess->getUser() === $user && $organizationAccess->isEditProject();
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