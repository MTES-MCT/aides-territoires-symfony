<?php

namespace App\Command\Script;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Service\Aid\AidService;
use App\Service\Api\InternalApiService;
use Symfony\Component\Routing\RouterInterface;
use App\Service\File\FileService;

#[AsCommand(name: 'at:script:compare_search', description: 'Compare les recherches')]
class CompareSearchCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Compare les recherches';
    protected string $commandTextEnd = '>Compare les recherches';



    public function __construct(
        private ProjectReferenceRepository $projectReferenceRepository,
        private AidService $aidService,
        private InternalApiService $internalApiService,
        private RouterInterface $routerInterface,
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

        $projectReferences = $this->projectReferenceRepository->findBy(
            [],
            ['name' => 'ASC']
        );

        $csvExportFolder = $this->fileService->getProjectDir() . '/datas/';
        $csvExportFile = $csvExportFolder . 'compare_search.csv';

        // création du fichier CSV
        $file = fopen($csvExportFile, 'w');
        fputcsv(
            $file,
            [
                'Projet référent',
                'Ancien comptage',
                'Nouveau comptage',
                'Différence',
                'Ancienne url',
                'Nouvelle url'
            ],
            ';'
        );

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

            $aidUrl = $this->routerInterface->generate(
                'app_aid_aid',
                [
                    'keyword' => $projectReference->getName()
                ],
                RouterInterface::ABSOLUTE_URL
            );

            // on écrit le csv
            fputcsv(
                $file,
                [
                    $projectReference->getName(),
                    $countOld,
                    $countNew,
                    $countNew - $countOld,
                    str_replace('http://localhost', 'https://aides-territoires.beta.gouv.fr', $aidUrl),
                    str_replace(
                        'http://localhost/aides',
                        'https://aides-terr-php-staging-pr445.osc-fr1.scalingo.io/testrecherche',
                        $aidUrl
                    ),
                ],
                ';'
            );
            $io->comment($projectReference->getName() . ' ' . $countOld . ' ' . $countNew);
        }

        fclose($file);

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
