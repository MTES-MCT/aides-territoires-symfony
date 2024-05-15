<?php

namespace App\Command\Cron\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Log\LogEvent;
use App\Entity\Search\SearchPage;
use App\Repository\Aid\AidRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use App\Service\Various\ParamService;
use Psr\Log\LoggerInterface;

#[AsCommand(name: 'at:cron:aid:count_live', description: 'Compte les aides lives')]
class CountLiveCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Cron';
    protected string $commandTextEnd = '>Cron';

    public function __construct(
        private KernelInterface $kernelInterface,
        private ManagerRegistry $managerRegistry,
        private AidService $aidService,
        private AidSearchFormService $aidSearchFormService,
        private EmailService $emailService,
        private ParamService $paramService,
        private LoggerInterface $loggerInterface
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try  {
            if ($this->kernelInterface->getEnvironment() != 'prod') {
                $io->info('Uniquement en prod');
                return Command::FAILURE;
            }

            // generate menu
            $this->cronTask($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function cronTask($input, $output)
    {
        try {
            $io = new SymfonyStyle($input, $output);

            /** @var AidRepository $aidRepo */
            $aidRepo = $this->managerRegistry->getRepository(Aid::class);
            
            // charge les aides publiées sans lien cassé de noté
            $nbAids = $aidRepo->countLives();

            $logEvent = new LogEvent();
            $logEvent->setCategory('aid');
            $logEvent->setEvent('live_count');
            $logEvent->setSource('aides-territoires');
            $logEvent->setValue($nbAids);
            $this->managerRegistry->getManager()->persist($logEvent);
    
            $searchPages = $this->managerRegistry->getRepository(SearchPage::class)->findAll();
            /** @var SearchPage $searchPage */
            foreach ($searchPages as $searchPage) {
                $aidParams = [
                    'showInSearch' => true,
                    'addSelect' => true
                ];
                $aidSearchClass = $this->aidSearchFormService->getAidSearchClass(
                    params: [
                        'querystring' => $searchPage->getSearchQuerystring() ?? null,
                        'forceOrganizationType' => null,
                        'dontUseUserPerimeter' => true
                        ]
                );

                $aidParams = array_merge($aidParams, $this->aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));
                $nbAids = $aidRepo->countAfterSelect($aidParams);

                $logEvent = new LogEvent();
                $logEvent->setCategory('aid');
                $logEvent->setEvent('live_count');
                $logEvent->setSource($searchPage->getSlug());
                $logEvent->setValue($nbAids);
    
                $this->managerRegistry->getManager()->persist($logEvent);
            }
    
            // sauvegarde
            $this->managerRegistry->getManager()->flush();
            
            // success
            $io->success('Comptage effectué');
            $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
        } catch (\Exception $e) {
            $this->loggerInterface->error($e->getMessage());
        }
    }
}
