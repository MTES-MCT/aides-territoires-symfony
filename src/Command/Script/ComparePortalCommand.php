<?php

namespace App\Command\Script;

use App\Entity\Search\SearchPage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\Search\SearchPageRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use Symfony\Component\Routing\RouterInterface;
use App\Service\File\FileService;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(name: 'at:script:compare_portal', description: 'Compare les portails')]
class ComparePortalCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Compare les portails';
    protected string $commandTextEnd = '>Compare les portails';



    public function __construct(
        private SearchPageRepository $searchPageRepository,
        private AidService $aidService,
        private AidSearchFormService $aidSearchFormService,
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

        $searchPages = $this->searchPageRepository->findAll();

        $csvExportFolder = $this->fileService->getProjectDir() . '/datas/';
        $csvExportFile = $csvExportFolder . 'compare_portal.csv';

        // création du fichier CSV
        $file = fopen($csvExportFile, 'w');
        fputcsv(
            $file,
            [
                'Portails',
                'Ancien comptage',
                'Nouveau comptage',
                'Différence',
                'Ancienne url',
                'Nouvelle url'
            ],
            ';'
        );

        foreach ($searchPages as $search_page) {
            $aidParams = [
                'showInSearch' => true,
            ];
            if (!$search_page->getOrganizationTypes()->isEmpty()) {
                $aidParams['organizationTypes'] = $search_page->getOrganizationTypes();
            }
            if (!$search_page->getCategories()->isEmpty()) {
                $aidParams['categories'] = $search_page->getCategories();
            }
            $queryString = null;
            try {
                // certaines pages ont un querystring avec https://... d'autres directement les parametres
                $query = parse_url($search_page->getSearchQuerystring())['query'] ?? null;
                $queryString = $query ?? $search_page->getSearchQuerystring();
            } catch (\Exception $e) {
                $queryString = null;
            }

            $aidSearchClass = $this->aidSearchFormService->getAidSearchClass(
                params: [
                    'querystring' => $queryString,
                    'forceOrganizationType' => null,
                    'dontUseUserPerimeter' => true
                ]
            );

            if (isset($aidParams['categories'])) {
                foreach ($aidParams['categories'] as $category) {
                    $aidSearchClass->addCategoryId($category);
                }
            }

            // parametres pour requetes aides
            $aidParams = array_merge(
                $aidParams,
                $this->aidSearchFormService->convertAidSearchClassToAidParams(
                    $aidSearchClass
                )
            );

            $aidParams['searchPage'] = $search_page;

            $countOld = $this->getOldCount($search_page);
            $countNew = count($this->aidService->searchAidsV2($aidParams));

            $aidUrl = $this->routerInterface->generate(
                'app_portal_portal_details',
                [
                    'slug' => $search_page->getSlug()
                ],
                RouterInterface::ABSOLUTE_URL
            );

            // on écrit le csv
            fputcsv(
                $file,
                [
                    $search_page->getName(),
                    $countOld,
                    $countNew,
                    $countNew - $countOld,
                    str_replace('http://localhost', 'https://aides-territoires.beta.gouv.fr', $aidUrl),
                    str_replace(
                        'http://localhost',
                        'https://aides-terr-php-staging-pr445.osc-fr1.scalingo.io',
                        $aidUrl
                    ),
                ],
                ';'
            );
            $io->comment($search_page->getName() . ' ' . $countOld . ' ' . $countNew);
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

    private function getOldCount(SearchPage $search_page): int
    {
        $searchPageUrl = $this->routerInterface->generate(
            'app_portal_portal_details',
            [
                'slug' => $search_page->getSlug()
            ],
            RouterInterface::ABSOLUTE_URL
        );
        $searchPageUrl = str_replace('http://localhost', 'https://aides-territoires.beta.gouv.fr', $searchPageUrl);

        $client = HttpClient::create();
        $response = $client->request('GET', $searchPageUrl);
        $content = $response->getContent();

        $crawler = new Crawler($content);
        $section = $crawler->filter('#aid-list');
        if ($section->count() > 0) {
            $h2Text = $section->filter('h2')->first()->text();
            // Extraire le nombre depuis le texte "XX dispositifs disponibles"
            $nb = (int) preg_replace('/[^0-9]/', '', $h2Text);
        } else {
            $nb = 0;
        }

        return $nb;
    }
}
