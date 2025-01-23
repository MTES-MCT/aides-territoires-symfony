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
            $searchParams = array_merge(
                $aidParams,
                [
                    'keyword' => $projectReference->getName(),
                    'orderBy' => [
                        'sort' => 'score_total',
                        'order' => 'DESC',
                    ],
                    'projectReference' => $projectReference,
                ],
            );
            // Trier les paramètres
            ksort($searchParams);
            // Trier orderBy
            ksort($searchParams['orderBy']);

            $this->aidService->searchAidsV3(
                $searchParams
            );
            $output->writeln('Cache '.$projectReference->getName().' préparé');
        }

        // préparation du cache sans filtres
        $searchParams = array_merge(
            $aidParams,
            [
                'orderBy' => [
                    'sort' => 'score_total',
                    'order' => 'DESC',
                ],
            ],
        );
        // Trier les paramètres
        ksort($searchParams);
        // Trier orderBy
        ksort($searchParams['orderBy']);
        $this->aidService->searchAidsV3(
            $searchParams
        );
        $output->writeln('Cache sans params préparé');

        return Command::SUCCESS;
    }
}
