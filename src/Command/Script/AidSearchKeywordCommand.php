<?php

namespace App\Command\Script;

use App\Entity\Aid\Aid;
use App\Message\Aid\AidExtractKeyword;
use App\Repository\Aid\AidRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'at:script:aid:search_keywords', description: 'Recherche des mots clés référents dans les aides')]
class AidSearchKeywordCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Recherche des mots clés référents dans les aides';
    protected string $commandTextEnd = '>Recherche des mots clés référents dans les aides';



    public function __construct(
        private ManagerRegistry $managerRegistry,
        private MessageBusInterface $bus,
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
            // import des keywords
            $this->sendToQueue($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function sendToQueue(InputInterface $input, OutputInterface $output): void
    {
        $timeStart = microtime(true);

        $io = new SymfonyStyle($input, $output);

        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        $aids = $aidRepository->findCustom(
            [
                'showInSearch' => true,
                'hasNoKeywordReference' => true,
            ]
        );
        $nbAids = count($aids);

        $batchSize = 100;
        $aidsChunks = array_chunk($aids, $batchSize);

        foreach ($aidsChunks as $aids) {
            foreach ($aids as $aid) {
                $this->bus->dispatch(new AidExtractKeyword($aid->getId()));
            }

            $this->managerRegistry->getManager()->clear();
        }

        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $io->success($nbAids . ' aides envoyées pour analyses');
        $io->success('Fin : ' . gmdate("H:i:s", (int) $timeEnd) . ' (' . gmdate("H:i:s", (int) $time) . ')');
        $io->success('Mémoire maximale utilisée : ' . intval(round(memory_get_peak_usage() / 1024 / 1024)) . ' MB');
    }
}
