<?php

namespace App\Command\Cron\Site;

use App\Repository\Reference\ProjectReferenceRepository;
use App\Service\Aid\AidService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'at:cron:site:redis_cache_reset', description: 'Vide le cache Redis')]
class RedisCacheResetCommand extends Command
{
    public function __construct(
        private CacheItemPoolInterface $cache,
        private AidService $aidService,
        private ProjectReferenceRepository $projectReferenceRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cache->clear();
        $output->writeln('Cache vidé avec succès');

        $aidParams = [
            'showInSearch' => true,
        ];

        $projectReferences = $this->projectReferenceRepository->findAll();

        // on parcours les projets référents pour préparer le cache
        foreach ($projectReferences as $projectReference) {
            $this->aidService->searchAidsV3(
                array_merge(
                    $aidParams,
                    [
                        'keyword' => $projectReference->getName(),
                        'orderBy' => [
                            'sort' => 'score_total',
                            'order' => 'DESC',
                        ],
                        'projectReference' => $projectReference,
                    ],
                )
            );
            $output->writeln('Cache '.$projectReference->getName());
        }

        // préparation du cache sans filtres
        $this->aidService->searchAidsV3(
            array_merge(
                $aidParams,
                [
                    'orderBy' => [
                        'sort' => 'score_total',
                        'order' => 'DESC',
                    ],
                ],
            )
        );
        $output->writeln('Cache sans params préparé');

        return Command::SUCCESS;
    }
}
