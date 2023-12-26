<?php
namespace App\Service\User;

use App\Entity\Log\LogUserAction;
use App\Entity\Log\LogUserLogin;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private AccessDecisionManagerInterface $accessDecisionManager,
        private AuthorizationCheckerInterface $authorizationCheckerInterface,
        private TokenStorageInterface $tokenStorageInterface,
        private Security $security
    )
    {
        
    }

    public function isMemberOfOrganization(?Organization $organization, User $user) : bool
    {
        $isMember = false;

        if (!$organization) {
            return true;
        }
        
        foreach ($user->getOrganizations() as $userOrganization) {
            if ($userOrganization->getId() === $organization->getId()) {
                $isMember = true;
            }
        }
        return $isMember;    
    }

    public function generateApiToken() : string {
        return sha1(uniqid(rand(), true));
    }

    /**
     * @return User|null
     */
    public function getUserLogged(): ?User
    {
        try {
            return $this->security->getUser();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param User $user
     * @param string $role
     * @return bool
     */
    public function isUserGranted(?User $user, string $role): bool
    {
        if (!$user) {
            return false;
        }
        $token = new UsernamePasswordToken($user, 'none', $user->getRoles());
        return ($this->accessDecisionManager->decide($token, [$role], $user));
    }


    /**
     *
     * @param User $user
     * @param User $targetUser
     * @return boolean
     */
    public function isUserMoreOrEqualGranted(User $user, User $targetUser): bool
    {
        $ok = true;
        foreach ($targetUser->getRoles() as $role) {
            if (!$this->isUserGranted($user, $role)) {
                $ok = false;
            }
        }
        return $ok;
    }

    /**
     *
     * @param array $params
     * @return void
     */
    public function setLogUser(array $params = null): void
    {
        $action = $params['action'] ?? null;
        $log = match ($action) {
            LogUserLogin::ACTION_LOGOUT, LogUserLogin::ACTION_LOST_PASSWORD, LogUserLogin::ACTION_LOGIN => new LogUserLogin(),
            default => new LogUserAction(),
        };

        $log->setUser($params['user'] ?? null);
        $log->setAction($action);
        $log->setType($params['type'] ?? null);
        $log->setData1($params['data1'] ?? null);
        $log->setData2($params['data2'] ?? null);
        $log->setIp($_SERVER['REMOTE_ADDR'] ?? null);
        $log->setUserAgent($_SERVER['HTTP_USER_AGENT'] ?? null);
        $log->setReferer($_SERVER['HTTP_REFERER'] ?? null);

        $this->entityManagerInterface->persist($log);
        $noFlush = $params['noFlush'] ?? null;

        if (!$noFlush) {
            $this->entityManagerInterface->flush();
        }
    }

    /**
     *  Returns the email address if the user is of a type listed in
     *  target_organization_types.
     *
     *  Returns an empty string otherwise.
     *
     * @param ?User $user
     * @return string
     */
    public function getSibEmailId(?User $user) : string
    {
        $targetOrganizationTypeSlugs = [
            OrganizationType::SLUG_COMMUNE,
            OrganizationType::SLUG_ECPI,
            OrganizationType::SLUG_DEPARTMENT,
            OrganizationType::SLUG_PUBLIC_ORG,
            OrganizationType::SLUG_REGION,
        ];
    
        $result = "";
    
        if ($user && $user->getDefaultOrganization() && $user->getDefaultOrganization()->getOrganizationType()) {
            if (in_array($user->getDefaultOrganization()->getOrganizationType()->getSlug(), $targetOrganizationTypeSlugs)) {
                $result = $user->getEmail();
            }
        }
    
        return $result;
    }
}
