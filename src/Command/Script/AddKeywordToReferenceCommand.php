<?php

namespace App\Command\Script;

use App\Entity\Aid\Aid;
use App\Entity\Reference\KeywordReference;
use App\Repository\Aid\AidRepository;
use App\Repository\Reference\KeywordReferenceRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:script:keywords_to_reference', description: 'Keywords vers keywordReferencets')]
class AddKeywordToReferenceCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Keywords vers keywordReference';
    protected string $commandTextEnd = '>Keywords vers keywordReference';



    public function __construct(
        private ManagerRegistry $managerRegistry
    ) {
        ini_set('max_execution_time', 60 * 60);
        ini_set('memory_limit', '1G');
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
            $this->importKeyword($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function importKeyword($input, $output): void
    {
        /** @var KeywordReferenceRepository $keywordReferenceRepository */
        $keywordReferenceRepository = $this->managerRegistry->getRepository(KeywordReference::class);

        $keywordReferences = $keywordReferenceRepository->findAll();

        $keywordReferencesByName = [];
        foreach ($keywordReferences as $keywordReference) {
            $keywordReferencesByName[$keywordReference->getName()] = $keywordReference;
        }

        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);
        $aids = $aidRepository->findCustom([
            'withOldKeywords' => true
        ]);

        foreach ($aids as $aid) {
            foreach ($aid->getKeywords() as $keyword) {
                $keywordReference = $keywordReferencesByName[$keyword->getName()] ?? null;
                if ($keywordReference) {
                    $aid->addKeywordReference($keywordReference);
                    $this->managerRegistry->getManager()->persist($aid);
                }
            }
        }

        $this->managerRegistry->getManager()->flush();
    }
}
