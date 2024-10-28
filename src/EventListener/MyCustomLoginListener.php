<?php

namespace App\EventListener;

use App\Entity\Log\LogUserLogin;
use App\Entity\User\User;
use App\Service\Matomo\MatomoService;
use App\Service\User\UserService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class MyCustomLoginListener
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ManagerRegistry $managerRegistry,
        private UserService $userService,
        private MatomoService $matomoService,
        private ParamService $paramService,
        private RequestStack $requestStack
    ) {
    }

    public function onSymfonyComponentSecurityHttpEventLoginSuccessEvent(LoginSuccessEvent $loginSuccessEvent)
    {
        // recupere user
        $user = $loginSuccessEvent->getUser();

        // la requete
        $request = $this->requestStack->getCurrentRequest();

        // Pour éviter d'être appellé par liip-imagine
        if ($request && preg_match('/\/media\/cache/', $request->getPathInfo())) {
            return;
        }

        // events login
        if ($user instanceof User) {
            // check autologin
            $autoLogin = $this->authorizationChecker->isGranted('IS_REMEMBERED');

            // stats login
            $this->userService->setLogUser(
                [
                    'user' => $user,
                    'action' => LogUserLogin::ACTION_LOGIN,
                    'type' => $autoLogin ? LogUserLogin::TYPE_AUTOLOGIN : null,
                    'noFlush' => true
                ]
            );

            // si première connexion
            if (!$user->getTimeLastLogin()) {
                $this->matomoService->trackGoal($this->paramService->get('goal_first_login'));
            }

            // met à jour date dernier login
            $user->setTimeLastLogin(new \DateTime(date('Y-m-d H:i:s')));
            $user->setDateLastLogin(new \DateTime(date('Y-m-d')));
            $this->managerRegistry->getManager()->persist($user);
            $this->managerRegistry->getManager()->flush();
        }
    }
}
