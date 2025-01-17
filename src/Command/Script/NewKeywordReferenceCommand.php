<?php

namespace App\Command\Script;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\File\FileService;
use App\Entity\Reference\KeywordReference;

#[AsCommand(name: 'at:script:new_keyword_reference', description: 'Import des mots-clés')]
class NewKeywordReferenceCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Import des mots-clés';
    protected string $commandTextEnd = '>Import des mots-clés';



    public function __construct(
        private ManagerRegistry $managerRegistry,
        private FileService $fileService
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

        $timeStart = microtime(true);

        $folder = $this->fileService->getProjectDir() . '/datas/';
        $filePath = $folder . 'new_keywords.csv';

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $io->error('Le fichier ' . $filePath . ' n\'existe pas');
            return Command::FAILURE;
        }

        $row = 0;
        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            if ($row == 0) {
                $row++;
                continue;
            }
            $row++;
            $intention = ((int)$data[0] == 1) ? true : false;
            $name = $data[1];
            $synonyms = explode(',', $data[2]);

            $keywordReference = new KeywordReference();
            $keywordReference->setIntention($intention);
            $keywordReference->setName($name);
            $keywordReference->setActive(true);
            $keywordReference->setParent($keywordReference);


            foreach ($synonyms as $synonymWord) {
                $synonymWord = trim($synonymWord);
                if ($synonymWord == '') {
                    continue;
                }
                $synonym = new KeywordReference();
                $synonym->setParent($keywordReference);
                $synonym->setIntention($intention);
                $synonym->setName($synonymWord);
                $synonym->setActive(true);
                $keywordReference->addKeywordReference($synonym);
            }

            $this->managerRegistry->getManager()->persist($keywordReference);
            $this->managerRegistry->getManager()->flush();
        }


        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $io->success(
            'Fin des opérations : '
            . gmdate("H:i:s", intval($timeEnd))
            . ' ('
            . gmdate("H:i:s", intval($time))
            . ')'
        );

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }
}
