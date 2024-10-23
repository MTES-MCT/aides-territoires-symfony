<?php

namespace App\Command\Script;

use App\Entity\Aid\Aid;
use App\Entity\Reference\KeywordReference;
use App\Repository\Aid\AidRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:script:import_keyword_aid', description: 'Transfert des mots-clés référents')]
class AidsAddKeywordReferencesCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Transfert des mots-clés référents';
    protected string $commandTextEnd = '>Transfert des mots-clés référents';



    public function __construct(
        protected ManagerRegistry $managerRegistry,
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
        /** @var AidRepository $aidRepo */
        $aidRepo = $this->managerRegistry->getRepository(Aid::class);

        $aids = $aidRepo->findWithKeywords();

        /** @var Aid $aid */
        foreach ($aids as $aid) {
            $keywords = $aid->getKeywords();
            foreach ($keywords as $keyword) {
                $keywordReference = $this->managerRegistry->getRepository(KeywordReference::class)
                    ->findOneBy(['name' => $keyword->getName()]);
                if ($keywordReference instanceof KeywordReference) {
                    $aid->addKeywordReference($keywordReference);
                    $this->managerRegistry->getManager()->persist($aid);
                }
            }
        }

        $this->managerRegistry->getManager()->flush();
    }
}
