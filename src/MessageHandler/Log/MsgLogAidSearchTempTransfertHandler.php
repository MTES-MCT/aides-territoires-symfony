<?php

namespace App\MessageHandler\Log;

use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogAidSearchTemp;
use App\Entity\User\FavoriteAid;
use App\Entity\User\User;
use App\Message\Log\MsgLogAidSearchTempTransfert;
use App\Repository\Log\LogAidSearchTempRepository;
use App\Repository\User\FavoriteAidRepository;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsMessageHandler()]
class MsgLogAidSearchTempTransfertHandler
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private NotificationService $notificationService,
        private ParamService $paramService,
        private MessageBusInterface $bus
    ) {
    }

    public function __invoke(MsgLogAidSearchTempTransfert $message): void
    {
        try {
            /** @var LogAidSearchTempRepository $logAidSearchTempRepository */
            $logAidSearchTempRepository = $this->managerRegistry->getRepository(LogAidSearchTemp::class);
            /** @var FavoriteAidRepository $favoriteAidRepository */
            $favoriteAidRepository = $this->managerRegistry->getRepository(FavoriteAid::class);
            
            $logAidSearchTemps = $logAidSearchTempRepository->findBy(
                ['dateCreate' => new \DateTime('yesterday')],
                null,
                500
            );

            $entityManager = $this->managerRegistry->getManager();
            $batchCount = 0;

            /** @var LogAidSearchTemp $logAidSearchTemp */
            foreach ($logAidSearchTemps as $logAidSearchTemp) {
                $logAidSearch = new LogAidSearch();
                $logAidSearch->setQuerystring($logAidSearchTemp->getQuerystring());
                $logAidSearch->setResultsCount($logAidSearchTemp->getResultsCount());
                $logAidSearch->setSource($logAidSearchTemp->getSource());
                $logAidSearch->setTimeCreate($logAidSearchTemp->getTimeCreate());
                $logAidSearch->setDateCreate($logAidSearchTemp->getDateCreate());
                $logAidSearch->setSearch($logAidSearchTemp->getSearch());
                $logAidSearch->setPerimeter($logAidSearchTemp->getPerimeter());
                $logAidSearch->setOrganization($logAidSearchTemp->getOrganization());
                $logAidSearch->setUser($logAidSearchTemp->getUser());
                foreach ($logAidSearchTemp->getOrganizationTypes() as $organizationType) {
                    $logAidSearch->addOrganizationType($organizationType);
                }
                foreach ($logAidSearchTemp->getBackers() as $backer) {
                    $logAidSearch->addBacker($backer);
                }
                foreach ($logAidSearchTemp->getCategories() as $category) {
                    $logAidSearch->addCategory($category);
                }
                foreach ($logAidSearchTemp->getPrograms() as $program) {
                    $logAidSearch->addProgram($program);
                }
                foreach ($logAidSearchTemp->getThemes() as $theme) {
                    $logAidSearch->addTheme($theme);
                }

                // si il y a des liaison savec favoriteAid on met à jour
                $favoriteAids = $favoriteAidRepository->findBy([
                    'logAidSearchTemp' => $logAidSearchTemp
                ]);
                foreach ($favoriteAids as $favoriteAid) {
                    $favoriteAid->setLogAidSearchTemp(null);
                    $favoriteAid->setLogAidSearch($logAidSearch);
                    $entityManager->persist($favoriteAid);
                }

                $entityManager->persist($logAidSearch);
                $entityManager->remove($logAidSearchTemp);

                if (++$batchCount % self::BATCH_SIZE === 0) {
                    $entityManager->flush();
                }
            }

            $entityManager->flush();

            // vérifie si il en reste
            $nbLogAidSearchTemps = $logAidSearchTempRepository->countCustom(
                [
                    'dateCreate' => new \DateTime('yesterday')
                ]
            );

            if ($nbLogAidSearchTemps > 0) {
                $this->bus->dispatch(new MsgLogAidSearchTempTransfert());
            }
        } catch (ProcessFailedException $exception) {
            // notif admin
            $admin = $this->managerRegistry->getRepository(User::class)
            ->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Erreur MsgLogAidViewTempTransfert',
                $exception->getMessage(),
            );
        }
    }
}
