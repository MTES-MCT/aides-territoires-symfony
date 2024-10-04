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
    const MESSAGE_ERROR = 'Vous n\'êtes pas autorisé à accéder à cette ressource.';
    const IDENTIFIER = 'USER_PROJECT_AIDS';

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
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        // l'utilisateur courant
        $currentUser = $this->userService->getUserLogged();

        if (
            $this->isUserConnected($currentUser)
            && (
                $this->isUserAuthor($currentUser, $subject)
                || $this->isUserMemberOfOrganization($currentUser, $subject)
                )
            ) {
            return true;
        }

        return false;
    }

    private function isUserConnected(?User $user) : bool 
    {
        if (!$user instanceof User || !$user->getId()) {
            return false;
        }
        return true;
    }

    private function isUserAuthor(?User $user, Project $project) : bool 
    {
        return $project->getAuthor()->getId() === $user->getId();
    }

    private function isUserMemberOfOrganization(?User $user, Project $project) : bool 
    {
        return $project->getOrganization()->getBeneficiairies()->contains($user);
    }
}
