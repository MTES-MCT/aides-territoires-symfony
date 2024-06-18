<?php

namespace App\Command\ImportFlux;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidFinancer;
use App\Entity\DataSource\DataSource;
use App\Service\Email\EmailService;
use App\Service\File\FileService;
use App\Service\Perimeter\PerimeterService;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'at:import_flux:generic', description: 'Import de flux générique, à étendre à chaque nouveau flux')]
class ImportFluxCommand extends Command // NOSONAR too much methods
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Import de flux générique, à étendre à chaque nouveau flux';
    protected string $commandTextEnd = '>Import de flux générique, à étendre à chaque nouveau flux';

    protected ?string $importUniqueidPrefix = null;
    protected ?int $idDataSource = null;

    protected array $aidsLabelSearch = ['result', 'results', 'aides', 'records', 'ListeDispositifs'];

    protected bool $paginationEnabled = false;
    protected int $nbPages = 1;
    protected int $nbByPages = 20;
    protected int $currentPage = 0;
    protected ?DataSource $dataSource;
    protected int $create = 0;
    protected int $update = 0;
    protected int $error = 0;
    protected \DateTime $dateImportStart;

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry,
        protected EmailService $emailService,
        protected ParamService $paramService,
        protected HttpClientInterface $httpClientInterface,
        protected HtmlSanitizerInterface $htmlSanitizerInterface,
        protected PerimeterService $perimeterService,
        protected StringService $stringService,
        protected FileService $fileService
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
        $this->dateImportStart = new \DateTime(date('Y-m-d H:i:s'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $timeStart = microtime(true);

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        // if ($this->kernelInterface->getEnvironment() != 'prod') {
        //     $io->info('Uniquement en prod');
        //     return Command::FAILURE;
        // }

        try  {
            // set la dataSource
            $this->dataSource = $this->managerRegistry->getRepository(DataSource::class)->find($this->idDataSource);
            if (!$this->dataSource instanceof DataSource) {
                throw new \Exception('Impossible de charger la dataSource : ' . $this->idDataSource);
            }

            // import du flux
            $this->importFlux($input, $output);
        } catch (\Exception $exception) {
            $this->emailService->sendEmail(
                $this->paramService->get('email_super_admin'),
                '[aides-territoires][Error] Erreur import flux',
                'emails/cron/import_flux/error.html.twig',
                [
                    'dataSource' => $this->dataSource,
                    'error' => $exception->getMessage(),
                ]
                );
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->success('Création : ' . $this->create);
        $io->success('Update : ' . $this->update);
        $io->success('Erreur : ' . $this->error);

        // met à jour le last access
        $this->dataSource->setTimeLastAccess(new \DateTime(date('Y-m-d H:i:s')));
        $this->managerRegistry->getManager()->persist($this->dataSource);
        $this->managerRegistry->getManager()->flush();
        
        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $io->success('Import flux terminé : '.gmdate("H:i:s", $timeEnd).' ('.gmdate("H:i:s", intval($time)).')');
        $io->success('Mémoire maximale utilisée : ' . intval(round(memory_get_peak_usage() / 1024 / 1024)) . ' MB');

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function importFlux($input, $output): void
    {
        // vérifie les paramètres requis
        $requiredParams = [
            'importUniqueidPrefix',
            'idDataSource'
        ];

        foreach ($requiredParams as $requiredParam) {
            if (empty($this->$requiredParam)) {
                throw new \Exception('Paramètre manquant : ' . $requiredParam);
            }
        }

        // si pagination
        if ($this->paginationEnabled) {
            $client = $this->getClient();
            // on recupère les infos pour la pagination avec un premier appel
            $response = $client->request(
                'GET',
                $this->dataSource->getImportApiUrl(),
                $this->getApiOptions()
            );
            $content = $response->toArray();
            $nbItems = $content['count'] ?? 0;
            if ($nbItems == 0) {
                $nbItems = $content['total_count'] ?? 0;
            }
            if (!$nbItems) {
                throw new \Exception('Erreur sur la pagination, nbItems = 0');
            }
            if (!$this->nbByPages) {
                throw new \Exception('Erreur sur la pagination, this->nbByPages = 0');
            }
            $this->nbPages = ceil($nbItems / $this->nbByPages);
        }
        
        // ouvre le flux pour recuperer les aides
        $aidsFormImport = $this->callApi();

        // progressbar
        $io = new SymfonyStyle($input, $output);
        $io->createProgressBar(count($aidsFormImport));

        // starts and displays the progress bar
        $io->progressStart();

        // importe les aides
        foreach ($aidsFormImport as $aidToImport) {
            $importUniqueid = $this->getImportUniqueid($aidToImport);

            if (empty($importUniqueid)) {
                continue;
            }

            // on regarde si on trouve une aide avec cet importUniqueid
            $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(
                [
                    'importUniqueid' => trim($importUniqueid)
                ]
            );

            if (!$aid instanceof Aid) {
                // on crée l'aide
                $this->createAid($aidToImport);
            } else {
                // on met à jour l'aide
                $this->updateAid($aidToImport, $aid);
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        // sauvegarde en base
        $this->managerRegistry->getManager()->flush();
    }

    protected function callApi()
    {
        $aidsFromImport = [];
        $client = $this->getClient();

        for ($i=0; $i<$this->nbPages; $i++) {
            $this->currentPage = $i;
            $importUrl = $this->dataSource->getImportApiUrl();
            if ($this->paginationEnabled) {
                if (strpos($importUrl, '?') !== false) {
                    $importUrl .= '&limit=' . $this->nbByPages . '&offset=' . ($this->currentPage * $this->nbByPages);
                } else {
                    $importUrl .= '?limit=' . $this->nbByPages . '&offset=' . ($this->currentPage * $this->nbByPages);
                }
            }
            try {
                $response = $client->request(
                    'GET',
                    $importUrl,
                    $this->getApiOptions()
                );
                $content = $response->getContent();
                $content = $response->toArray();
    
                foreach ($content as $key => $value) {
                    if (in_array($key, $this->aidsLabelSearch) && is_array($value)) {
                        $aidsFromImport = array_merge($aidsFromImport, $value);
                        break;
                    }
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
            if (!count($aidsFromImport)) {
                throw new \Exception('Le flux ne contient aucune aide');
            }
        }


        return $aidsFromImport;
    }

    // The aid is actually new, so we just create it.
    protected function createAid($aidToImport): bool
    {
        try {
            // créer l'aide
            $aid = new Aid();
            $aid->setIsImported(true);
            $aid->setStatus(Aid::STATUS_REVIEWABLE);
            $aid->setImportDataSource($this->dataSource);
            $aid->setImportUniqueid($this->getImportUniqueid($aidToImport));
            $aid->setImportDataUrl($this->dataSource->getImportDataUrl());
            $importLicence = $this->dataSource->getImportLicence() ?? DataSource::SLUG_LICENCE_UNKNOWN;
            $aid->setImportShareLicence($importLicence);
            $aid->setImportRawObject($aidToImport);
            $aid->setAuthor($this->dataSource->getAidAuthor());
            $aidFinancer = new AidFinancer();
            $aidFinancer->setBacker($this->dataSource->getBacker());
            $aid->addAidFinancer($aidFinancer);
            $aid->setPerimeter($this->dataSource->getPerimeter());
            $aid = $this->setCategories($aidToImport, $aid);
            $aid = $this->setAidTypes($aidToImport, $aid);
            $aid = $this->setAidRecurrence($aidToImport, $aid);
            $aid = $this->setAidSteps($aidToImport, $aid);
            $aid = $this->setAidAudiences($aidToImport, $aid);
            $aid = $this->setKeywords($aidToImport, $aid);
            $aid = $this->setAidDestinations($aidToImport, $aid);
            // si il y a besoin de re-définir le périmètre autre que celui du dataSource
            $aid = $this->setPerimeter($aidToImport, $aid);

            foreach ($this->getFieldsMapping($aidToImport, ['context' => 'create']) as $field => $value) {
                $aid->{'set' . ucfirst($field)}($value);
            }
            // recup auto des champ import_..._temp si non renseigné
            if (!$aid->getImportRawObjectTemp()) {
                $aid->setImportRawObjectTemp($aid->getImportRawObject());
            }
            if (!$aid->getImportRawObjectTempCalendar()) {
                $aid->setImportRawObjectTempCalendar($aid->getImportRawObjectCalendar());
            }
            
            // prépare pour sauvegarde
            $this->managerRegistry->getManager()->persist($aid);

            // update la dataSource
            $this->dataSource->addAid($aid);
            $this->managerRegistry->getManager()->persist($this->dataSource);

            // incrémente le compteur
            $this->create++;

            // retour
            return true;
            }
        catch (\Exception $e) {
            throw new \Exception('Impossible de créer l\'aide : ' . $e->getMessage());
        }
    }

    # The aid is known from a previous import, in that case,
    # we just update a few fields but we don't overwrite some
    # manual modifications that could have been made from our side.
    protected function updateAid($aidToImport, $aid): bool
    {
        try {
            // les nouvelles valeurs
            $newValues = $this->getFieldsMapping($aidToImport, ['context' => 'update', 'aid' => $aid]);
            $keepValues = [];

            if (
                isset($newValues['importRawObject'])
                && $newValues['importRawObject'] != $aid->getImportRawObject()
            ) {
                /*
                If fields other than :
                    - aid.submission_deadline,
                    - aid.start_date,
                    - aid.name_initial
                have been modified.
                We won't update automotically the aid.
                Aid's status is "reviewable" and we simply update the fields calendar
                and the fields:
                    - import_raw_object_temp et
                    - import_raw_object_temp_calendar
                    - start_date
                    - submission_deadline
                    - name_initial
                */
                $keepValues = ['dateStart', 'dateSubmissionDeadline', 'nameInitial', 'importRawObject', 'importRawObjectCalendar'];
                $aid->setImportUpdated(true);
            } elseif (
                isset($newValues['importRawObjectCalendar'])
                && $newValues['importRawObjectCalendar'] != $aid->getImportRawObjectCalendar()
                && $this->idDataSource !== 2
            ) {
                /*
                If the changed fields are:
                    - aid.submission_deadline,
                    - aid.start_date,
                    - aid.name_initial,
                and if the import_data_source is not Pays de la Loire
                we try an automatic update of these fields.
                We also update the field import_raw_object_temp_calendar
                */
                $keepValues = ['dateStart', 'dateSubmissionDeadline', 'nameInitial', 'importRawObjectCalendar'];
                $aid->setDateImportLastAccess(new \DateTime(date('Y-m-d H:i:s')));
            }

            // parcours les nouvelles valeurs
            $entityUpdated = false;
            foreach ($newValues as $field => $value) {
                if (!in_array($field, $keepValues)) {
                    continue;
                }
                if ($field == 'importRawObject') {
                    $aid->setImportRawObjectTemp($value);
                    unset($newValues[$field]);
                } elseif ($field == 'importRawObjectCalendar') {
                    $aid->setImportRawObjectTempCalendar($value);
                    unset($newValues[$field]);
                } else {
                    $aid->{'set' . ucfirst($field)}($value);
                }
                $entityUpdated = true;
            }

            // on regarde si modification des audiences
            $oldArray = $aid->getAidAudiences()->toArray();
            $aid = $this->setAidAudiences($aidToImport, $aid);
            $newArray = $aid->getAidAudiences()->toArray();

            $diff = array_diff($oldArray, $newArray);
            if (!empty($diff)) {
                $entityUpdated = true;
            }
            //-----------------------------------------------------


            // on regarde si modification des categories
            $oldArray = $aid->getCategories()->toArray();
            $aid = $this->setCategories($aidToImport, $aid);
            $newArray = $aid->getCategories()->toArray();

            $diff = array_diff($oldArray, $newArray);
            if (!empty($diff)) {
                $entityUpdated = true;
            }
            //-----------------------------------------------------

            if ($entityUpdated) {
                // notifie que l'aide à été modifié suite à l'import
                $aid->setImportUpdated(true);
                // persiste
                $this->managerRegistry->getManager()->persist($aid);

                // incrémente le compteur
                $this->update++;
            }

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Impossible de mettre à jour l\'aide : ' . $e->getMessage());
        }
    }

    protected function getClient(): HttpClientInterface
    {
        return $this->httpClientInterface;
    }
    
    protected function getApiOptions(): array
    {
        return [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];
    }

    protected function getImportUniqueid($aidToImport): ?string
    {
        return null;
    }

    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        return [];
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        return $aid;
    }

    protected function setAidTypes(array $aidToImport, Aid $aid): Aid // NOSONAR methode generique pour surcharge
    {
        return $aid;
    }

    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid // NOSONAR methode generique pour surcharge
    {
        return $aid;
    }

    protected function setAidSteps(array $aidToImport, Aid $aid): Aid // NOSONAR methode generique pour surcharge
    {
        return $aid;
    }

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid // NOSONAR methode generique pour surcharge
    {
        return $aid;
    }

    protected function setKeywords(array $aidToImport, Aid $aid): Aid // NOSONAR methode generique pour surcharge
    {
        return $aid;
    }

    protected function setAidDestinations(array $aidToImport, Aid $aid): Aid // NOSONAR methode generique pour surcharge
    {
        return $aid;
    }

    protected function setPerimeter(array $aidToImport, Aid $aid): Aid // NOSONAR methode generique pour surcharge
    {
        return $aid;
    }

    protected function setIsCallForProject(array $aidToImport, Aid $aid): Aid // NOSONAR methode generique pour surcharge
    {
        return $aid;
    }

    protected function getImportRaws(
        array $aidToImport,
        array $keys = ['start_date', 'predeposit_date', 'submission_deadline', 'recurrence']
    ): array
    {
        $importRawObjectCalendar = [];
        foreach ($keys as $key) {
            if (isset($aidToImport[$key])) {
                $importRawObjectCalendar[$key] = $aidToImport[$key];
            }
        }
        if (empty($importRawObjectCalendar)) {
            $importRawObjectCalendar = null;
        }

        $importRawObject = $aidToImport;
        foreach ($keys as $key) {
            if (isset($importRawObject[$key])) {
                unset($importRawObject[$key]);
            }
        }

        return [
            'importRawObjectCalendar' => $importRawObjectCalendar,
            'importRawObject' => $importRawObject
        ];
    }

    protected function getDateTimeOrNull(?string $date): ?\DateTime
    {
        if (!$date) {
            return null;
        }
        try {
            $date = new \DateTime($date);
        } catch (\Exception $e) {
            $date = null;
        }
        return $date;
    }

    protected function cleanName(string $name): string
    {
        $name = $this->stringService->cleanString($name);
        if (strlen($name) > 255) {
            $name = $this->stringService->truncate($name, 255);
        }
        return $name;
    }

    protected function getHtmlOrNull(string $html): ?string
    {
        return $this->getCleanHtml($html) == '' ? null : $this->getCleanHtml($html);
    }

    protected function getCleanHtml(string $html): string
    {
        try {
            return $this->htmlSanitizerInterface->sanitize($html);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function concatHtmlFields(array $aidToImport, array $fields, ?string $separator = null): ?string
    {
        $html = '';
        foreach ($fields as $field) {
            if (isset($aidToImport[$field]) && trim($aidToImport[$field]) != '') {
                $html .= ' ' . $this->getCleanHtml($aidToImport[$field]);
                if ($separator) {
                    $html .= $separator;
                }
            }
        }

        $html = trim($html);

        return $html !== '' ? $html : null;
    }
}
