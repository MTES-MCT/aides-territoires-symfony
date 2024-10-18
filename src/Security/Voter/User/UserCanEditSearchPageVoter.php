<?php

namespace App\Security\Voter\User;

use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Service\User\UserService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserCanEditSearchPageVoter extends Voter
{
    public const MESSAGE_ERROR = 'Vous n\'êtes pas autorisé à accéder à cette ressource.';
    public const IDENTIFIER = 'USER_ACCESS_SEARCH_PAGE';

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
            && ($this->isUserAdministrator($currentUser, $subject) || $this->isUserEditor($currentUser, $subject))
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

    private function isUserAdministrator(?User $user, SearchPage $searchPage): bool
    {
        if ($searchPage->getAdministrator()->getId() !== $user->getId()) {
            return false;
        }

        return true;
    }

    private function isUserEditor(?User $user, SearchPage $searchPage): bool
    {
        if (!$searchPage->getEditors()->contains($user)) {
            return false;
        }

        return true;
    }
}
