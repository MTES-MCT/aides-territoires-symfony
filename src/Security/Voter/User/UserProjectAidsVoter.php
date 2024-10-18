<?php

namespace App\Security\Voter\User;

use App\Entity\Project\Project;
use App\Entity\User\User;
use App\Service\User\UserService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserProjectAidsVoter extends Voter
{
    public const MESSAGE_ERROR = 'Vous n\'êtes pas autorisé à accéder à cette ressource.';
    public const IDENTIFIER = 'USER_PROJECT_AIDS';

    public function __construct(
        private RequestStack $requestStack,
        private UserService $userService
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::IDENTIFIER;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // Récupérer l'utilisateur optionnel depuis le sujet
        $optionalUser = $subject['user'] ?? null;
        $project = $subject['project'] ?? null;

        if (!$project instanceof Project) {
            return false;
        }

        // l'utilisateur courant ou l'utilisateur optionnel
        $currentUser = $optionalUser ?? $this->userService->getUserLogged();


        if (
            $this->isUserConnected($currentUser)
            && (
                $this->isUserAuthor($currentUser, $project)
                || $this->isUserMemberOfOrganization($currentUser, $project)
            )
        ) {
            return true;
        }

        return false;
    }

    private function isUserConnected(?User $user): bool
    {
        if (!$user instanceof User || !$user->getId()) {
            return false;
        }
        return true;
    }

    private function isUserAuthor(?User $user, Project $project): bool
    {
        return $project->getAuthor()->getId() === $user->getId();
    }

    private function isUserMemberOfOrganization(?User $user, Project $project): bool
    {
        return $project->getOrganization()->getBeneficiairies()->contains($user);
    }
}
