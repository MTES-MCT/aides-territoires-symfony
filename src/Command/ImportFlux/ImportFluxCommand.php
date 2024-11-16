<?php

namespace App\Command\ImportFlux;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidType;
use App\Entity\Category\Category;
use App\Entity\DataSource\DataSource;
use App\Entity\Organization\OrganizationType;
use App\Entity\User\User;
use App\Service\Email\EmailService;
use App\Service\File\FileService;
use App\Service\Notification\NotificationService;
use App\Service\Perimeter\PerimeterService;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use App\Validator\UrlExternalValid;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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

    /**
     *
     * @var string[]
     */
    protected array $aidsLabelSearch = ['result', 'results', 'aides', 'records', 'ListeDispositifs'];

    protected ?AidRecurrence $aidRecurrenceOneOff = null;
    protected ?AidRecurrence $aidRecurrenceOnGoing = null;
    protected ?AidRecurrence $aidRecurrenceRecurring = null;

    /** @var array<int, AidType> */
    protected array $aidTypesById = [];
    /** @var array<string, array<int, Category>> */
    protected array $aidCategoriesMapping = [];
    /** @var array<int, OrganizationType> */
    protected array $organizationTypesById = [];
    protected bool $paginationEnabled = false;
    protected int $nbPages = 1;
    protected int $nbByPages = 20;
    protected int $currentPage = 0;
    protected ?DataSource $dataSource;
    protected int $create = 0;
    protected int $update = 0;
    protected int $error = 0;
    protected \DateTime $dateImportStart;

    /**
     *
     * @var string[]
     */
    protected array $thematiquesOk = [];
    /**
     *
     * @var string[]
     */
    protected array $thematiquesKo = [];

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry,
        protected EmailService $emailService,
        protected ParamService $paramService,
        protected HttpClientInterface $httpClientInterface,
        protected HtmlSanitizerInterface $htmlSanitizerInterface,
        protected PerimeterService $perimeterService,
        protected StringService $stringService,
        protected FileService $fileService,
        protected ValidatorInterface $validator,
        protected NotificationService $notificationService
    ) {
        parent::__construct();
        $this->dateImportStart = new \DateTime(date('Y-m-d H:i:s'));
        $this->setInternalAidRecurrences();
        $this->setAidTypesById();
        $this->setOrganizationTypesById();
        $this->setAidCategoriesMapping();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $timeStart = microtime(true);

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        // if ('prod' != $this->kernelInterface->getEnvironment()) {
        //     $io->info('Uniquement en prod');

        //     return Command::FAILURE;
        // }

        try {
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

        // notif admin
        $admin = $this->managerRegistry->getRepository(User::class)
            ->findOneBy(['email' => $this->paramService->get('email_super_admin')]);
        $this->notificationService->addNotification(
            $admin,
            'Rapport flux '. $this->dataSource->getName(),
            'Création : '.$this->create.'<br />'
            . 'Update : '.$this->update.'<br />'
            . 'Erreur : '.$this->error
        );

        // met à jour le last access
        $this->dataSource->setTimeLastAccess(new \DateTime(date('Y-m-d H:i:s')));
        $this->managerRegistry->getManager()->persist($this->dataSource);
        $this->managerRegistry->getManager()->flush();

        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $io->success(
            'Import flux terminé : ' . gmdate("H:i:s", intval($timeEnd)) . ' (' . gmdate("H:i:s", intval($time)) . ')'
        );
        $io->success(
            'Mémoire maximale utilisée : ' . intval(round(memory_get_peak_usage() / 1024 / 1024)) . ' MB'
        );

        $io->title($this->commandTextEnd);

        return Command::SUCCESS;
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function importFlux($input, $output): void
    {
        // vérifie les paramètres requis
        $requiredParams = [
            'importUniqueidPrefix',
            'idDataSource',
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

            if (!$nbItems) {
                throw new \Exception('Erreur sur la pagination, nbItems = 0');
            }
            if (!$this->nbByPages) {
                throw new \Exception('Erreur sur la pagination, this->nbByPages = 0');
            }
            $this->nbPages = (int) ceil($nbItems / $this->nbByPages);
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
            $aid = $this->findAid($aidToImport);

            if (!$aid instanceof Aid) {
                // on crée l'aide
                $this->createAid($aidToImport);
            } else {
                // on met à jour l'aide
                $this->updateAid($aidToImport, $aid);
            }

            $io->progressAdvance();
        }
        $this->thematiquesOk = array_unique($this->thematiquesOk);
        $this->thematiquesKo = array_unique($this->thematiquesKo);
        sort($this->thematiquesOk);
        sort($this->thematiquesKo);

        $io->progressFinish();

        // sauvegarde en base
        $this->managerRegistry->getManager()->flush();
    }

    /**
     * appel le flux
     *
     * @return array<int, mixed>
     */
    protected function callApi(): array
    {
        $aidsFromImport = [];
        $client = $this->getClient();

        for ($i = 0; $i < $this->nbPages; ++$i) {
            $this->currentPage = $i;
            $importUrl = $this->dataSource->getImportApiUrl();
            if ($this->paginationEnabled) {
                $importUrl .= '?limit=' . $this->nbByPages . '&offset=' . ($this->currentPage * $this->nbByPages);
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

    /**
     *
     * @param array<mixed, mixed> $aidToImport
     * @return ?Aid
     */
    protected function findAid(array $aidToImport): ?Aid
    {
        try {
            // on recherche par importUniqueid
            $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(
                [
                    'importUniqueid' => trim($this->getImportUniqueid($aidToImport)),
                ]
            );
            if ($aid instanceof Aid) {
                return $aid;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Nouvelle aide à créer
     *
     * @param array<mixed, mixed> $aidToImport
     * @return bool
     */
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

            // prépare pour sauvegarde
            $this->managerRegistry->getManager()->persist($aid);

            // update la dataSource
            $this->dataSource->addAid($aid);
            $this->managerRegistry->getManager()->persist($this->dataSource);

            // incrémente le compteur
            ++$this->create;

            // retour
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Impossible de créer l\'aide : ' . $e->getMessage());
        }
    }

    /**
     * Aide à mettre à jour
     * Certains champs sont mis à jour automatiquement, d'autres sont soumis à validation manuelle
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return bool
     */
    protected function updateAid($aidToImport, $aid): bool
    {
        try {
            // les nouvelles valeurs
            $newValues = $this->getFieldsMapping($aidToImport, ['context' => 'update', 'aid' => $aid]);
            // en update on ne touche pas au nom (modifié par les bizdev lors de la validation)
            if (isset($newValues['importDatas']) && isset($newValues['importDatas']['name'])) {
                unset($newValues['importDatas']['name']);
            }
            // liste des champs qu'on met à jour automatiquement, les autres sont soumis à validation manuelle
            $keepValues = [
                'dateStart',
                'dateSubmissionDeadline',
                'nameInitial',
                'originUrl',
                'applicationUrl',
                'importDataMention',
            ];

            // parcours les nouvelles valeurs
            $entityUpdated = false;
            $needManualValidation = false;
            foreach ($newValues as $field => $value) {
                // on ne regarde pas le champ qui stocke l'update
                if ('importDatas' == $field) {
                    continue;
                }
                // gestion des booleéns
                $methodGet = 'get';
                if (!method_exists($aid, 'get' . ucfirst($field))) {
                    if (method_exists($aid, 'is' . ucfirst($field))) {
                        $methodGet = 'is';
                    } else {
                        continue;
                    }
                }

                // les champs qu'on ne modifie pas automatiquement
                if (!in_array($field, $keepValues)) {
                    // on regarde si il y a une modification pour mettre un statut "à valider" à l'aide
                    if ($aid->{$methodGet . ucfirst($field)}() != $value) {
                        $entityUpdated = true;
                        $needManualValidation = true;
                    }
                    continue;
                }

                // les champs qu'on modifie automatiquement
                if ($aid->{$methodGet . ucfirst($field)}() != $value && method_exists($aid, 'set' . ucfirst($field))) {
                    // assigne la nouvelle valeur
                    $aid->{'set' . ucfirst($field)}($value);
                    // on retire le champ des valeurs à validées manuellement
                    unset($newValues[$field]);
                    // on note que l'entité à été modifiée
                    $entityUpdated = true;
                }
            }

            // on assigne les valeurs de l'api au champ importDatas
            $aid->setImportDatas($newValues['importDatas'] ?? null);

            // on regarde si modification des audiences, update en auto
            $oldArray = $aid->getAidAudiences()->toArray();
            $aid = $this->setAidAudiences($aidToImport, $aid);
            $newArray = $aid->getAidAudiences()->toArray();

            $diff = array_diff($oldArray, $newArray);
            if (!empty($diff)) {
                $entityUpdated = true;
            }
            // -----------------------------------------------------

            // on regarde si modification des categories, update en auto
            $oldArray = $aid->getCategories()->toArray();
            $aid = $this->setCategories($aidToImport, $aid);
            $newArray = $aid->getCategories()->toArray();

            $diff = array_diff($oldArray, $newArray);
            if (!empty($diff)) {
                $entityUpdated = true;
            }
            // -----------------------------------------------------

            if ($entityUpdated) {
                // on ne notifie que si l'aide est en ligne et a besoin d'une validation manuelle
                if ($aid->isLive() && $needManualValidation) {
                    // notifie que l'aide à été modifié suite à l'import
                    $aid->setImportUpdated(true);
                    // si les infos de contact ont été modifié
                    $aid->setContactInfoUpdated($this->isContactUpdated($newValues, $aid));
                }
                // persiste
                $this->managerRegistry->getManager()->persist($aid);

                // incrémente le compteur
                ++$this->update;
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

    /**
     *
     * @return array<mixed, mixed> $aidToImport
     */
    protected function getApiOptions(): array
    {
        return [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @return string|null
     */
    protected function getImportUniqueid(array $aidToImport): ?string
    {
        return null;
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param array<mixed, mixed> $params
     * @return array<mixed, mixed>
     */
    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        return [];
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        return $aid;
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setAidTypes(array $aidToImport, Aid $aid): Aid // NOSONAR
    {
        return $aid;
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid // NOSONAR
    {
        return $aid;
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setAidSteps(array $aidToImport, Aid $aid): Aid // NOSONAR
    {
        return $aid;
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid // NOSONAR
    {
        return $aid;
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setKeywords(array $aidToImport, Aid $aid): Aid // NOSONAR
    {
        return $aid;
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setAidDestinations(array $aidToImport, Aid $aid): Aid // NOSONAR
    {
        return $aid;
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setPerimeter(array $aidToImport, Aid $aid): Aid // NOSONAR
    {
        return $aid;
    }

    /**
     * methode generique pour surcharge
     *
     * @param array<mixed, mixed> $aidToImport
     * @param Aid $aid
     * @return Aid
     */
    protected function setIsCallForProject(array $aidToImport, Aid $aid): Aid // NOSONAR
    {
        return $aid;
    }

    /**
     * @param array<mixed, mixed> $return
     * @return array<mixed, mixed>
     */
    protected function mergeImportDatas(array $return): array
    {
        return array_merge($return, [
            'importDatas' => $return,
        ]);
    }

    /**
     * Undocumented function
     *
     * @param array<string, string> $newValues
     * @param Aid $aid
     * @return boolean
     */
    protected function isContactUpdated(array $newValues, Aid $aid): bool
    {
        $checkFields = ['contact', 'originUrl', 'applicationUrl'];
        foreach ($checkFields as $field) {
            if (
                isset($newValues[$field])
                && method_exists($aid, 'get' . ucfirst($field))
                && $aid->{'get' . ucfirst($field)}() != $newValues[$field]
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * @param string|null $date
     * @param array<string, mixed>|null $params
     * @return \DateTime|null
     */
    protected function getDateTimeOrNull(?string $date, ?array $params = null): ?\DateTime
    {
        if (!$date) {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y'];
        $dateTemp = null;

        foreach ($formats as $format) {
            $dateTemp = \DateTime::createFromFormat($format, $date);
            if (false !== $dateTemp) {
                break;
            }
        }

        if (false === $dateTemp) {
            return null;
        }

        if (!isset($params['keepTime'])) {
            // Force pour éviter les différences sur le fuseau horaire
            $date = new \DateTime($dateTemp->format('Y-m-d'));
            // Force les heures, minutes, et secondes à 00:00:00
            $date->setTime(0, 0, 0);
        } else {
            $date = $dateTemp;
        }

        return $date;
    }

    /**
     *
     * @param array<mixed, mixed>|null $aidToImport
     * @param string|null $key
     * @return boolean|null
     */
    protected function getBooleanOrNull(?array $aidToImport, ?string $key): ?bool
    {
        if (!is_array($aidToImport) || !$key || !isset($aidToImport[$key])) {
            return null;
        }
        $value = trim(strtolower($aidToImport[$key]));

        return '1' == $value || 'true' == $value || 'oui' == $value;
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
        return '' == $this->getCleanHtml($html) ? null : $this->getCleanHtml($html);
    }

    protected function getCleanHtml(string $html): string
    {
        try {
            return $this->htmlSanitizerInterface->sanitize($html);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @param array<mixed, mixed> $aidToImport
     * @param string[] $fields
     * @param string|null $separator
     * @return string|null
     */
    public function concatHtmlFields(array $aidToImport, array $fields, ?string $separator = null): ?string
    {
        $html = '';
        foreach ($fields as $field) {
            if (isset($aidToImport[$field]) && '' != trim($aidToImport[$field])) {
                $html .= ' ' . $this->getCleanHtml($aidToImport[$field]);
                if ($separator) {
                    $html .= $separator;
                }
            }
        }

        $html = trim($html);

        return '' !== $html ? $html : null;
    }

    protected function setInternalAidRecurrences(): void
    {
        // méthode générique pour surcharge
    }

    protected function setAidTypesById(): void
    {
        // méthode générique pour surcharge
    }

    protected function setOrganizationTypesById(): void
    {
        // méthode générique pour surcharge
    }

    protected function setAidCategoriesMapping(): void
    {
        // méthode générique pour surcharge
    }

    protected function getValidExternalUrlOrNull(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $url = trim($url);

        // on vérifie d'abord que l'url est valide
        $constraint = new UrlExternalValid();
        $violations = $this->validator->validate($url, $constraint);
        if (count($violations) > 0) {
            return null;
        }

        return $url;
    }
}
