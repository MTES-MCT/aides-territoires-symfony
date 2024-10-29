<?php

namespace App\Command\Script;

use App\Service\Reference\ReferenceService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:script:keyword_global', description: 'Import des mots-clés')]
class KeywordsGlobalCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Import des mots-clés';
    protected string $commandTextEnd = '>Import des mots-clés';



    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ReferenceService $referenceService
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


        $commands = [
            // les nouveaux mots clés référent
            'at:script:keyword_import_references',
            // on recupere les keywordsynonyms pour les ajouter aux keyword references
            'at:script:keyword_synonyms_to_reference',
            // on associe les mots clés aux aides
            'at:script:import_keyword_aid',
            // ancien keywords en kewordReference
            'at:script:keywords_to_reference',
            // on gestion mots clés / projets référents
            'at:script:project_reference:keywords'
        ];

        $timeStart = microtime(true);

        foreach ($commands as $command) {
            try {
                $timeStartOperation = microtime(true);
                $command = $this->getApplication()->find($command);

                $arguments = [];

                $greetInput = new ArrayInput($arguments);
                $command->run($greetInput, $output);

                $timeEnd = microtime(true);
                $timeOperation = $timeEnd - $timeStartOperation;
                $timeGlobal = $timeEnd - $timeStart;

                $io->success(
                    'Fin '
                        . $command->getName()
                        . ' : '
                        . gmdate("H:i:s", $timeEnd)
                        . ' | ope : '
                        . gmdate("H:i:s", $timeOperation)
                        . ' | global : '
                        . gmdate("H:i:s", $timeGlobal)
                );

                // nettoyage memoire
                gc_collect_cycles();
                $this->managerRegistry->getManager()->clear();
            } catch (\Exception $exception) {
                $io->error($exception->getMessage());
                exit;
            }
        }

        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $io->success('Fin des opérations : ' . gmdate("H:i:s", $timeEnd) . ' (' . gmdate("H:i:s", $time) . ')');

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }
}
