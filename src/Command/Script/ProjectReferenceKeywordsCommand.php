<?php

namespace App\Command\Script;

use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Repository\Reference\KeywordReferenceRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:script:project_reference:keywords', description: 'Exclusion de certains mots clés pour certains projets')]
class ProjectReferenceKeywordsCommand extends Command
{

    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Exclusion de certains mots clés pour certains projets';
    protected string $commandTextEnd = '>Exclusion de certains mots clés pour certains projets';



    public function __construct(
        protected ManagerRegistry $managerRegistry
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
            $this->task($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function task($input, $output): void
    {
        /** @var ProjectReferenceRepository $projectReferenceRepository */
        $projectReferenceRepository = $this->managerRegistry->getRepository(ProjectReference::class);

        /** @var KeywordReferenceRepository $keywordReferenceRepository */
        $keywordReferenceRepository = $this->managerRegistry->getRepository(KeywordReference::class);

        $placeKeyword = $keywordReferenceRepository->findOneBy(['name' => 'place']);

        $terrainKeyword = $keywordReferenceRepository->findOneBy(['name' => 'terrain']);

        $maisonKeyword = $keywordReferenceRepository->findOneBy(['name' => 'maison']);

        $patrimoineKeyword = $keywordReferenceRepository->findOneBy(['name' => 'patrimoine']);

        $fenetreKeyword = $keywordReferenceRepository->findOneBy(['name' => 'fenêtre']);

        $porteKeyword = $keywordReferenceRepository->findOneBy(['name' => 'porte']);

        $sociauxKeyword = $keywordReferenceRepository->findOneBy(['name' => 'sociaux']);

        // tous les projets
        $projectReferences = $projectReferenceRepository->findAll();

        // on exclus  / requiert certains mots clés
        foreach ($projectReferences as $projectReference) {
            if (strpos($projectReference->getName(), 'place') !== false) {
                $projectReference->addExcludedKeywordReference($placeKeyword);
            }
            if (strpos($projectReference->getName(), 'terrain') !== false) {
                $projectReference->addExcludedKeywordReference($terrainKeyword);
            }
            if (strpos($projectReference->getName(), 'maison') !== false) {
                $projectReference->addExcludedKeywordReference($maisonKeyword);
            }
            if (strpos($projectReference->getName(), 'patrimoine religieux') !== false) {
                $projectReference->addExcludedKeywordReference($patrimoineKeyword);
            }

            if ($projectReference->getName() == 'Création de logements sociaux') {
                $projectReference->addRequiredKeywordReference($sociauxKeyword);
            }

            if ($projectReference->getName() == 'Changement des fenêtres/portes d’un bâtiment public') {
                $projectReference->addRequiredKeywordReference($fenetreKeyword);
                $projectReference->addRequiredKeywordReference($porteKeyword);
            }

            $this->managerRegistry->getManager()->persist($projectReference);
        }

        $this->managerRegistry->getManager()->flush();
    }
}
