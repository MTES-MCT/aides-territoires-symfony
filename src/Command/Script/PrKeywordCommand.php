<?php

namespace App\Command\Script;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Repository\Reference\KeywordReferenceRepository;

#[AsCommand(name: 'at:script:pr_new_keyword', description: 'Associations mots cles')]
class PrKeywordCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Associations mots cles';
    protected string $commandTextEnd = '>Associations mots cles';



    public function __construct(
        private ProjectReferenceRepository $projectReferenceRepository,
        private KeywordReferenceRepository $keywordReferenceRepository,
        private ManagerRegistry $managerRegistry,
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

        $projects = [
            'Rénovation énergétique école' => [
                'requires'  => ['rénovation énergétique'],
                'excludes' => []
            ],
            'Gestion des inondations' => [
                'requires'  => [],
                'excludes' => ['gestion des déchets']
            ],
            'Construction d’une cantine scolaire' => [
                'requires'  => ['cantine'],
                'excludes' => []
            ],
            'Installation de miroir de circulation de sécurité routière' => [
                'requires'  => ['miroir voirie'],
                'excludes' => []
            ],
            'Réaménagement de la cantine scolaire / Acquisition de mobiliers et matériels pour les cantines' => [
                'requires'  => ['cantine'],
                'excludes' => []
            ],

        ];
        foreach ($projects as $projectName => $associates) {
            $projectReference = $this->projectReferenceRepository->findOneBy(['name' => $projectName]);
            if (!$projectReference) {
                $io->error('Projet non trouvé : ' . $projectName);
                continue;
            }

            // les requis
            foreach ($associates['requires'] as $require) {
                $keywordReference = $this->keywordReferenceRepository->findOneBy(
                    ['name' => $require],
                    ['id' => 'DESC']
                );
                if (!$keywordReference) {
                    $io->error('Mot clé non trouvé : ' . $require);
                } else {
                    $projectReference->addRequiredKeywordReference($keywordReference);
                }
            }

            // les exclus
            foreach ($associates['excludes'] as $exclude) {
                $keywordReference = $this->keywordReferenceRepository->findOneBy(
                    ['name' => $exclude],
                    ['id' => 'DESC']
                );
                if (!$keywordReference) {
                    $io->error('Mot clé non trouvé : ' . $exclude);
                } else {
                    $projectReference->addExcludedKeywordReference($keywordReference);
                }
            }

            $this->managerRegistry->getManager()->persist($projectReference);
        }

        $this->managerRegistry->getManager()->flush();



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
