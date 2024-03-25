<?php

namespace App\Command\Script;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Category\Category;
use App\Service\Reference\ReferenceService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:script:structure_aides', description: 'Update de la structure des aides')]
class StructureAidesCommand extends Command
{

    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Update de la structure des aides';
    protected string $commandTextEnd = '>Update de la structure des aides';

    

    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ReferenceService $referenceService
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function configure() : void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);


        $timeStart = microtime(true);

        // > Catégorie : animation et mise en réseau => Type  : ingénierie animation et mise en réseau
        // le  nouveau types d'aides
        $newType = $this->managerRegistry->getRepository(AidType::class)->findOneBy([
            'slug' => AidType::SLUG_INGENIERIE_ANIMATION_RESEAU
        ]);

        // recupére toutes les aides avec “sous catégorie” animation et mise en réseau 
        $category = $this->managerRegistry->getRepository(Category::class)->findOneBy([
            'slug' => Category::SLUG_ANIMATION_MISE_EN_RESEAU
        ]);
        $aids = $this->managerRegistry->getRepository(Aid::class)->findCustom([
            'categories' => [$category]
        ]);


        // progressbar
        $io->createProgressBar(count($aids));
        $io->progressStart(count($aids));
        
        /** @var Aid $aid */
        foreach ($aids as $aid) {
            $aid->removeCategory($category);
            $aid->addAidType($newType);
            $this->managerRegistry->getManager()->persist($aid);
            $io->progressAdvance();
        }

        // sauvegarde
        $this->managerRegistry->getManager()->flush();
        $io->progressFinish();

        // < Catégorie : animation et mise en réseau => Type  : ingénierie animation et mise en réseau
        
        // > Catégorie : Valorisation d'actions => Type  : ingénierie animation et mise en réseau + Step : Suivi / évaluation
        // le  nouveau types d'aides
        $newType = $this->managerRegistry->getRepository(AidType::class)->findOneBy([
            'slug' => AidType::SLUG_INGENIERIE_ANIMATION_RESEAU
        ]);

        // l'étape
        $newAidStep = $this->managerRegistry->getRepository(AidStep::class)->findOneBy([
            'slug' => AidStep::SLUG_POSTOP
        ]);

        // recupére toutes les aides avec “sous catégorie” animation et mise en réseau 
        $category = $this->managerRegistry->getRepository(Category::class)->findOneBy([
            'slug' => Category::SLUG_VALORISATION_ACTIONS_PROJETS
        ]);
        $aids = $this->managerRegistry->getRepository(Aid::class)->findCustom([
            'categories' => [$category]
        ]);


        // progressbar
        $io->createProgressBar(count($aids));
        $io->progressStart(count($aids));
        
        /** @var Aid $aid */
        foreach ($aids as $aid) {
            $aid->removeCategory($category);
            $aid->addAidType($newType);
            $aid->addAidStep($newAidStep);
            $this->managerRegistry->getManager()->persist($aid);
            $io->progressAdvance();
        }

        // sauvegarde
        $this->managerRegistry->getManager()->flush();
        $io->progressFinish();

        // < Catégorie : Valorisation d'actions => Type  : ingénierie animation et mise en réseau + Step : Suivi / évaluation

        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $io->success('Fin des opérations : '.gmdate("H:i:s", $timeEnd).' ('.gmdate("H:i:s", $time).')');
        $io->success('Mémoire maximale utilisée : ' . intval(round(memory_get_peak_usage() / 1024 / 1024)) . ' MB');

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }    
}