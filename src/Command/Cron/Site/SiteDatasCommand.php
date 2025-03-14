<?php

namespace App\Command\Cron\Site;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Log\LogEvent;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Reference\ProjectReference;
use App\Entity\Search\SearchPage;
use App\Message\Backer\BackerCountAid;
use App\Message\Log\MsgDeleteOldLogAdmin;
use App\Message\Log\MsgLogAidSearchTempTransfert;
use App\Message\Log\MsgLogAidViewTempTransfert;
use App\Message\Perimeter\CountyCountBacker;
use App\Message\Reference\ProjectReferenceCountAids;
use App\Message\SearchPage\SearchPageCountAid;
use App\Repository\Aid\AidRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'at:cron:site:datas', description: 'Cron Datas du site')]
class SiteDatasCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Cron Datas du site';
    protected string $commandTextEnd = '>Cron Datas du site';

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private MessageBusInterface $bus
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try {
            // tache
            $this->cronTask($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function cronTask(InputInterface $input, OutputInterface $output): void
    {
        $timeStart = microtime(true);

        $io = new SymfonyStyle($input, $output);

        // Les projets référents
        $this->projectReferences();

        // // // comptage des porteurs par département
        $this->countyCountBacker();

        // // comptage d'aide par porteur
        $this->backerCountAid();

        // // compte les aides lives totales et par portail
        $this->countAidsLive();

        // transfert les logs
        $this->transfertLogs();

        // supprime les logs admins trop anciens
        $this->deleteOldLogAdmins();

        // le temps passé
        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        // success
        $io->success(
            'Temps écoulé : '
            . gmdate("H:i:s", intval($timeEnd))
            . ' ('
            . gmdate("H:i:s", intval($time))
            . ')'
        );
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }

    // envoi les projets référents pour compter le nombre de résultats de recherche
    private function projectReferences(): void
    {
        /** @var ProjectReferenceRepository $projectReferenceRepository */
        $projectReferenceRepository = $this->managerRegistry->getRepository(ProjectReference::class);

        $projectReferences = $projectReferenceRepository->findAll();

        foreach ($projectReferences as $projectReference) {
            // on envoi le projet pour comptage
            $this->bus->dispatch(new ProjectReferenceCountAids($projectReference->getId()));
        }
    }

    private function countyCountBacker(): void
    {
        /** @var PerimeterRepository $perimeterRepo */
        $perimeterRepo = $this->managerRegistry->getRepository(Perimeter::class);

        // charge les départements
        $counties = $perimeterRepo->findCounties();

        // pour chaque département, compte le nombre de backer
        foreach ($counties as $county) {
            $this->bus->dispatch(new CountyCountBacker($county->getId()));
        }
    }

    private function backerCountAid(): void
    {
        // charge les porteurs d'aides
        $backers = $this->managerRegistry->getRepository(Backer::class)->findAll();

        // pour chaque backer, compte le nombre d'aides
        /** @var Backer $backer */
        foreach ($backers as $backer) {
            $this->bus->dispatch(new BackerCountAid($backer->getId()));
        }
    }

    private function countAidsLive(): void
    {
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
        $this->managerRegistry->getManager()->flush();

        $searchPages = $this->managerRegistry->getRepository(SearchPage::class)->findAll();
        /** @var SearchPage $searchPage */
        foreach ($searchPages as $searchPage) {
            $this->bus->dispatch(new SearchPageCountAid($searchPage->getId()));
        }
    }

    private function transfertLogs(): void
    {
        $this->bus->dispatch(new MsgLogAidSearchTempTransfert());
        $this->bus->dispatch(new MsgLogAidViewTempTransfert());
    }

    private function deleteOldLogAdmins(): void
    {
        $this->bus->dispatch(new MsgDeleteOldLogAdmin());
    }
}
