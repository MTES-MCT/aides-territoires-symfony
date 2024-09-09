<?php

namespace App\MessageHandler\Log;

use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogAidSearchTemp;
use App\Entity\User\User;
use App\Message\Log\MsgLogAidViewTempTransfert;
use App\Repository\Log\LogAidSearchTempRepository;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsMessageHandler()]
class MsgLogAidSearchTempTransfertHandler
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private KernelInterface $kernelInterface,
        private NotificationService $notificationService,
        private ParamService $paramService
    ) {
    }

    public function __invoke(MsgLogAidViewTempTransfert $message): void
    {
        try {
            /** @var LogAidSearchTempRepository $logaidSearchTempRepository */
            $logAidSearchTempRepository = $this->managerRegistry->getRepository(LogAidSearchTemp::class);
            
            $logAidSearchTemps = $logAidSearchTempRepository->findBy(['dateCreate' => new \DateTime('yesterday')]);

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

                $entityManager->persist($logAidSearch);
                $entityManager->remove($logAidSearchTemp);
                
                if (++$batchCount % self::BATCH_SIZE === 0) {
                    $entityManager->flush();
                }
            }

            $entityManager->flush();
            $entityManager->clear();
            
        } catch (ProcessFailedException $exception) {
            // notif admin
            $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Erreur MsgLogAidViewTempTransfert',
                $exception->getMessage(),
            );
        }
    }
}
