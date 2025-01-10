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
use App\Repository\Reference\ProjectReferenceRepository;
use App\Repository\Aid\AidRepository;
use App\Service\Aid\AidService;
use App\Service\Various\ParamService;
use App\Service\Api\InternalApiService;

#[AsCommand(name: 'at:script:compare_search', description: 'Compare les recherches')]
class CompareSearchCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Compare les recherches';
    protected string $commandTextEnd = '>Compare les recherches';



    public function __construct(
        private ProjectReferenceRepository $projectReferenceRepository,
        private AidRepository $aidRepository,
        private AidService $aidService,
        private ParamService $paramService,
        private InternalApiService $internalApiService
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

        $projectReferences = $this->projectReferenceRepository->findBy(
            [],
            ['name' => 'ASC']
        );

        $xAuthKey = $this->paramService->get('X_AUTH_KEY');

        foreach ($projectReferences as $projectReference) {
            $aidParams = [
                'keyword' => $projectReference->getName(),
                'projectReference' => $projectReference,
                'showInSearch' => true,
            ];

            $reponse = $this->internalApiService->callApi(
                '/aids',
                [
                    'keyword' => $projectReference->getName()
                ],
                'GET'
            );
            $response = json_decode($reponse, true);

            $countOld = $response['count'];
            $countNew = count($this->aidService->searchAidsV2($aidParams));
            $io->comment($projectReference->getName() . ' ' . $countOld . ' ' . $countNew);
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
