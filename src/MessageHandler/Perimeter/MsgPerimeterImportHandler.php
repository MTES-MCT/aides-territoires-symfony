<?php

namespace App\MessageHandler\Perimeter;

use App\Entity\User\User;
use App\Message\Perimeter\MsgPerimeterImport;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsMessageHandler()]
class MsgPerimeterImportHandler
{
    public function __construct(
        private ManagerRegistry $managerR<?php

        namespace App\MessageHandler\Perimeter;
        
        use App\Entity\Backer\Backer;
        use App\Entity\Perimeter\Perimeter;
        use App\Message\Perimeter\CountyCountBacker;
        use Symfony\Component\Messenger\Attribute\AsMessageHandler;
        use Doctrine\Persistence\ManagerRegistry;
        
        #[AsMessageHandler()]
        class CountyCountBackerHandler
        {
            public function __construct(
                private ManagerRegistry $managerRegistry,
            ) {
            }
        
            public function __invoke(CountyCountBacker $message): void
            {
                /** @var PerimeterRepository $perimeterRepo */
                $perimeterRepo = $this->managerRegistry->getRepository(Perimeter::class);
        
                /** @var BackerRepository $backerRepo */
                $backerRepo = $this->managerRegistry->getRepository(Backer::class);
                
                // charge le département
                $county = $perimeterRepo->find($message->getIdPerimeter());
        
                // met à jour le nombre de porteurs
                if ($county instanceof Perimeter) {
                    $county->setBackersCount($backerRepo->countBackerWithAidInCounty(['id' => $county->getId()]));
                    $this->managerRegistry->getManager()->persist($county);
                    $this->managerRegistry->getManager()->flush();
                }
            }
        }
        egistry,
        private KernelInterface $kernelInterface,
        private NotificationService $notificationService,
        private ParamService $paramService
    ) {
    }
    public function __invoke(MsgPerimeterImport $message): void
    {
        $command = ['php', 'bin/console', 'at:cron:perimeter:perimeter_import'];

        $process = new Process($command);
        $process->setWorkingDirectory($this->kernelInterface->getProjectDir()); // Assurez-vous de définir le bon répertoire de travail

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            // notif admin
            $admin = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
            $this->notificationService->addNotification(
                $admin,
                'Envoi MsgPerimeterImport',
                $exception->getMessage(),
            );
        }
    }
}
