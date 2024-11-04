<?php

namespace App\Service\Export;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Cron\CronExportSpreadsheet;
use App\Entity\Organization\Organization;
use App\Entity\Project\Project;
use App\Entity\User\User;
use App\Exception\InvalidFileFormatException as ExceptionInvalidFileFormatException;
use App\Message\Export\MsgSpreadsheetToExport;
use App\Repository\Aid\AidRepository;
use App\Repository\User\UserRepository;
use App\Service\File\FileService;
use App\Service\User\UserService;
use App\Service\Various\StringService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;

class SpreadsheetExporterService
{
    public const EXCEPTION_FORMAT_NOT_SUPPORTED_MESSAGE = 'Format non supporté';
    public const TODAY_DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private Environment $twig,
        private UserService $userService,
        private ManagerRegistry $managerRegistry,
        private FileService $fileService,
        private HtmlSanitizerInterface $htmlSanitizerInterface,
        private LoggerInterface $loggerInterface,
        private MessageBusInterface $messageBusInterface,
        private StringService $stringService
    ) {
    }
    public function createResponseFromQueryBuilder(// NOSONAR too complex
        QueryBuilder $queryBuilder,
        mixed $entityFcqn,
        string $filename,
        string $format = FileService::FORMAT_CSV
    ): StreamedResponse|Response {
        ini_set('max_execution_time', 60 * 60);
        ini_set('memory_limit', '1G');

        // compte le nombre de résultats
        $queryBuilderCount = clone $queryBuilder;
        $count = $queryBuilderCount->select('COUNT(entity)')->getQuery()->getSingleScalarResult();

        // si trop de résultats, on va traiter par le worker
        if ($count > 1000) {
            $params = $queryBuilder->getQuery()->getParameters();
            $sqlParams = [];

            foreach ($params as $param) {
                // si ArrayCollection
                if ($param->getValue() instanceof ArrayCollection) {
                    $values = [];
                    foreach ($param->getValue() as $value) {
                        $values[] = $value->getId();
                    }
                    $sqlParams[] = ['name' => $param->getName(), 'value' => $values];
                    // Si object
                } elseif (is_object($param->getValue())) {
                    $sqlParams[] = ['name' => $param->getName(), 'value' => $param->getValue()->getId()];
                    // Si string
                } else {
                    $sqlParams[] = ['name' => $param->getName(), 'value' => $param->getValue()];
                }
            }

            $cronExportSpreadsheet = new CronExportSpreadsheet();
            $cronExportSpreadsheet->setSqlRequest($queryBuilder->getQuery()->getDQL());
            $cronExportSpreadsheet->setSqlParams($sqlParams);
            $cronExportSpreadsheet->setEntityFqcn($entityFcqn);
            $cronExportSpreadsheet->setFilename($filename . '.' . $format);
            $cronExportSpreadsheet->setFormat($format);
            $cronExportSpreadsheet->setUser($this->userService->getUserLogged());

            $this->managerRegistry->getManager()->persist($cronExportSpreadsheet);
            $this->managerRegistry->getManager()->flush();

            // envoi au worker
            $this->messageBusInterface->dispatch(new MsgSpreadsheetToExport());

            $content = $this->twig->render('admin/cron/cron-launched.html.twig', []);

            return new Response($content);
        }

        // on peu retourner directement un fichier tableur
        $response = new StreamedResponse();
        $response->setCallback(function () use ($queryBuilder, $entityFcqn, $filename, $format) {
            $entity = new $entityFcqn();

            $results = $queryBuilder->getQuery()->getResult();
            $datas = $this->getDatasFromEntityType($entity, $results);

            if ($format == FileService::FORMAT_CSV) {
                $options = new \OpenSpout\Writer\CSV\Options();
                $options->FIELD_DELIMITER = ';';
                $options->FIELD_ENCLOSURE = '"';

                $writer = new \OpenSpout\Writer\CSV\Writer($options);
            } elseif ($format == FileService::FORMAT_XLSX) {
                $sheetView = new SheetView();
                $writer = new \OpenSpout\Writer\XLSX\Writer();
            } else {
                throw new ExceptionInvalidFileFormatException(self::EXCEPTION_FORMAT_NOT_SUPPORTED_MESSAGE);
            }

            $now = new \DateTime(date(self::TODAY_DATE_FORMAT));
            $writer->openToBrowser('export_' . $filename . '_at_' . $now->format('d_m_Y') . '.' . $format);

            if ($format == FileService::FORMAT_XLSX && isset($sheetView)) {
                $writer->getCurrentSheet()->setSheetView($sheetView);
            }
            $headers = [];
            if (isset($datas[0])) {
                $headers = array_keys($datas[0]);
            }

            $cells = [];
            foreach ($headers as $csvHeader) {
                $cells[] = Cell::fromValue($csvHeader);
            }

            /** add a row at a time */
            $singleRow = new Row($cells);
            $writer->addRow($singleRow);

            foreach ($datas as $data) {
                $cells = [];
                foreach ($data as $value) {
                    $cells[] = Cell::fromValue($value);
                }
                $singleRow = new Row($cells);
                $writer->addRow($singleRow);
            }

            $writer->close();
        });

        return $response;
    }

    /**
     *
     * @param mixed $entity
     * @param mixed $results
     * @return array<int, array<string, mixed>>
     */
    public function getDatasFromEntityType(mixed $entity, mixed $results): array // NOSONAR too complex
    {
        $datas = [];
        switch (get_class($entity)) {
            case Aid::class:
                /** @var Aid $result */
                foreach ($results as $key => $result) {
                    $audiences = [];
                    foreach ($result->getAidAudiences() as $aidAudience) {
                        $audiences[] = $aidAudience->getName();
                    }
                    $categories = [];
                    foreach ($result->getCategories() as $category) {
                        $categories[] = $category->getName();
                    }
                    $types = [];
                    foreach ($result->getAidTypes() as $type) {
                        $types[] = $type->getName();
                    }
                    $destinations = [];
                    foreach ($result->getAidDestinations() as $destination) {
                        $destinations[] = $destination->getName();
                    }
                    $aidSteps = [];
                    foreach ($result->getAidSteps() as $step) {
                        $aidSteps[] = $step->getName();
                    }
                    $programs = [];
                    foreach ($result->getPrograms() as $program) {
                        $programs[] = $program->getName();
                    }
                    $backers = [];
                    foreach ($result->getAidFinancers() as $backer) {
                        if ($backer->getBacker() instanceof Backer) {
                            $backers[] = $backer->getBacker()->getName();
                        }
                    }
                    $instructors = [];
                    foreach ($result->getAidInstructors() as $instructor) {
                        if ($instructor->getBacker() instanceof Backer) {
                            $instructors[] = $instructor->getBacker()->getName();
                        }
                    }
                    $rate = '';
                    if ($result->getSubventionRateMin()) {
                        $rate .= ' Min : ' . $result->getSubventionRateMin();
                    }
                    if ($result->getSubventionRateMax()) {
                        $rate .= ' Max : ' . $result->getSubventionRateMax();
                    }
                    $datas[] = [
                        'Nom de l\'aide' => $result->getName(),
                        'Programmes' => join("\n", $programs),
                        'Nom initial' => $result->getNameInitial(),
                        'Porteurs d’aides' => join("\n", $backers),
                        'Instructeurs de l\'aide' => join("\n", $instructors),
                        'Bénéficiaires' => join("\n", $audiences),
                        'Types d\'aide' => join("\n", $types),
                        'Taux de subvention, min. et max. (en %, nombre entier)' => $rate,
                        'Taux de subvention (commentaire optionnel)' => $result->getSubventionComment(),
                        'Appel à projet / Manifestation d’intérêt' => $result->isIsCallForProject() ? 'Oui' : 'Non',
                        'Description' => $this->truncateHtml($result->getDescription()),
                        'Exemples d\'applications' => $this->truncateHtml($result->getProjectExamples()),
                        'Sous thématiques' => join("\n", $categories),
                        'Récurrence' => $result->getAidRecurrence() ? $result->getAidRecurrence()->getName() : '',
                        'Date d\'ouverture' => $result->getDateStart() ? $result->getDateStart()->format('d/m/Y') : '',
                        'Date de clôture' => $result->getDateSubmissionDeadline()
                            ? $result->getDateSubmissionDeadline()->format('d/m/Y')
                            : '',
                        'Conditions d\'éligibilité' => $this->truncateHtml($result->getEligibility()),
                        'État d\'avancement du projet pour bénéficier du dispositif' => join("\n", $aidSteps),
                        'Types de dépenses / actions couvertes' => join("\n", $destinations),
                        'Zone géographique couverte par l\'aide*' => $result->getPerimeter()
                            ? $result->getPerimeter()->getName()
                            : '',

                        'Lien vers le descriptif complet' => $result->getOriginUrl(),
                        'Lien vers la démarche en ligne' => $result->getApplicationUrl(),
                        'Contact(s) pour candidater' => $this->truncateHtml($result->getContact()),

                        'Auteur de l\'aide' => $result->getAuthor() ? $result->getAuthor()->getEmail() : '',

                        'url' => $result->getUrl(),
                        'Statut' => $result->getStatus(),
                    ];
                    unset($results[$key]);
                }
                break;
            case User::class:
                /** @var UserRepository $userRepository */
                $userRepository = $this->managerRegistry->getRepository(User::class);

                /** @var User $result */
                foreach ($results as $key => $result) {
                    $projectsHaveAids = false;
                    $nbProjectWithAids = $userRepository->countProjectWithAids([
                        'email' => $result->getEmail()
                    ]);
                    $projectsHaveAids = $nbProjectWithAids > 0;

                    /** @var ?Organization $defaultOrganization */
                    $defaultOrganization = $this->userService->getDefaultOrganizationByEmail($result->getEmail());

                    $datas[] = [
                        'id' => $result->getId(),
                        'Prénom' => $result->getFirstname(),
                        'Nom' => $result->getLastname(),
                        'Adresse e-mail' => $result->getEmail(),
                        'Numéro de téléphone' => $result->getContributorContactPhone(),
                        'Périmètre de l\'organization' => ($defaultOrganization && $defaultOrganization->getPerimeter())
                            ? $defaultOrganization->getPerimeter()->getName()
                            : '',
                        'Périmètre (region)' => ($defaultOrganization && $defaultOrganization->getPerimeterRegion())
                            ? $defaultOrganization->getPerimeterRegion()->getName()
                            : '',
                        'Périmètre (Département)' =>
                            ($defaultOrganization && $defaultOrganization->getPerimeterDepartment())
                                ? $defaultOrganization->getPerimeterDepartment()->getName()
                                : '',
                        'Périmètre (Population)' =>
                            ($defaultOrganization && $defaultOrganization->getPerimeter())
                                ? $defaultOrganization->getPerimeter()->getPopulation()
                                : '',
                        'Contributeur ?' => $result->isIsContributor() ? 'Oui' : 'Non',
                        'Bénéficiaire ?' => $result->isIsBeneficiary() ? 'Oui' : 'Non',
                        'Nombre d\'aides' => count($result->getAids()),
                        'Structure du bénéficiaire' => $defaultOrganization ? $defaultOrganization->getName() : '',
                        'ID de l\'organisation' => $defaultOrganization ? $defaultOrganization->getId() : '',
                        'Nombre de projets de l\'organisation' =>
                            ($defaultOrganization)
                                ? count($defaultOrganization->getProjects())
                                : 0,
                        'Présence d\'aides associées à un projet' => $projectsHaveAids ? 'VRAI' : '',
                        'Organisme (ancien champ)' => $result->getContributorOrganization(),
                        'Fonction du bénéficiaire' => $result->getBeneficiaryFunction(),
                        'Rôle (ancien champ)' => $result->getContributorRole(),
                        'Rôle du bénéficiaire' => $result->getBeneficiaryRole(),
                        'Date de création' => $result->getTimeCreate()
                            ? $result->getTimeCreate()->format(self::TODAY_DATE_FORMAT)
                            : '',
                        'Date de mise à jour' => $result->getTimeUpdate()
                            ? $result->getTimeUpdate()->format(self::TODAY_DATE_FORMAT)
                            : '',
                        'dernière connexion' => $result->getTimeLastLogin()
                            ? $result->getTimeLastLogin()->format(self::TODAY_DATE_FORMAT)
                            : '',
                        'Type de structure' => ($defaultOrganization && $defaultOrganization->getOrganizationType())
                            ? $defaultOrganization->getOrganizationType()->getName()
                            : '',
                        'Code postal de la structure' => $defaultOrganization ? $defaultOrganization->getZipCode() : '',
                    ];
                    unset($results[$key]);
                    unset($defaultOrganization);
                }

                break;

            case Project::class:
                /** @var Project $result */
                foreach ($results as $key => $result) {
                    $regions = ($result->getOrganization() && $result->getOrganization()->getPerimeter())
                        ? $result->getOrganization()->getPerimeter()->getRegions()
                        : '';
                    if (is_array($regions)) {
                        $regions = implode(',', $regions);
                    }
                    $counties = ($result->getOrganization() && $result->getOrganization()->getPerimeter())
                        ? $result->getOrganization()->getPerimeter()->getDepartments()
                        : '';
                    if (is_array($counties)) {
                        $counties = implode(',', $counties);
                    }

                    $projectTypes = [];
                    foreach ($result->getKeywordSynonymlists() as $keywordSynonymlist) {
                        $projectTypes[] = $keywordSynonymlist->getName();
                    }

                    $datas[] = [
                        'Nom' => $result->getName(),
                        'Organisation' => $result->getOrganization() ? $result->getOrganization()->getName() : '',
                        'Description' => $result->getDescription(),
                        'Périmètre du porteur de projet' =>
                            ($result->getOrganization() && $result->getOrganization()->getPerimeter())
                                ? $result->getOrganization()->getPerimeter()->getName()
                                : '',
                        'Périmètre (Région)' => $regions,
                        'Périmètre (Département)' => $counties,
                        'Est public' => $result->isIsPublic() ? 'Oui' : 'Non',
                        'Date création' => $result->getTimeCreate()
                            ? $result->getTimeCreate()->format('d/m/Y H:i:s')
                            : '',
                        'Projet référent' => $result->getProjectReference()
                            ? $result->getProjectReference()->getName()
                            : '',
                        'Types de projet (ancien système)' => join(',', $projectTypes),
                    ];
                    unset($results[$key]);
                }
                break;

            case Backer::class:
                /** @var Backer $result */
                foreach ($results as $key => $result) {
                    $regions = ($result->getPerimeter()) ? $result->getPerimeter()->getRegions() : '';
                    if (is_array($regions)) {
                        $regions = implode(',', $regions);
                    }
                    $counties = ($result->getPerimeter()) ? $result->getPerimeter()->getDepartments() : '';
                    if (is_array($counties)) {
                        $counties = implode(',', $counties);
                    }

                    $aidsParams = [
                        'backer' => $result,
                    ];
                    /** @var AidRepository $aidRepo */
                    $aidRepo = $this->managerRegistry->getRepository(Aid::class);
                    $aids = $aidRepo->findCustom($aidsParams);
                    $nbAidsLive = 0;
                    foreach ($aids as $aid) {
                        if ($aid->isLive()) {
                            $nbAidsLive++;
                        }
                    }


                    $datas[] = [
                        'Nom du porteur' => $result->getName(),
                        'Périmètre' => $result->getPerimeter() ? $result->getPerimeter()->getName() : '',
                        'Périmètre (région)' => $regions,
                        'Périmètre (département)' => $counties,
                        'Groupe de porteur' => $result->getBackerGroup() ? $result->getBackerGroup()->getName() : '',
                        'Nombre d’aides' => count($aids),
                        'Nombre d’aides publiées' => $nbAidsLive
                    ];
                    unset($results[$key]);
                }
                break;
            default:
                break;
        }
        return $datas;
    }

    public function exportProjectAids(Project $project, string $format = 'csv'): Response
    {
        try {
            // Création du tableur
            $spreadsheet = $this->getProjectAidsSpreadsheet($project);

            if ($format == FileService::FORMAT_CSV) {
                // Passage au format CSV
                $writer = new Csv($spreadsheet);
                $writer->setDelimiter(';');
                $writer->setEnclosure('"');
                $contentType = 'text/csv';
            } elseif ($format == FileService::FORMAT_XLSX) {
                // Passage au format Xlsx
                $writer = new Xlsx($spreadsheet);
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            } else {
                throw new ExceptionInvalidFileFormatException(self::EXCEPTION_FORMAT_NOT_SUPPORTED_MESSAGE);
            }

            $now = new \DateTime(date(self::TODAY_DATE_FORMAT));
            $filename = 'Aides-territoires_-_' . $now->format('Y-m-d') . '_-_' . $project->getSlug() . '.' . $format;

            // StreamedResponse pour le téléchargement
            $response = new StreamedResponse(function () use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', $contentType);
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        } catch (\Exception $e) {
            $this->loggerInterface->error('Erreur exportProjectAids', [
                'exception' => $e,
                'idProject' => $project->getId(),
            ]);

            // Retour erreur
            return new Response(
                'Erreur lors de l\'exportation des aides du projet',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function getProjectAidsSpreadsheet(Project $project): Spreadsheet
    {
        // Création du tableur
        $spreadsheet = new Spreadsheet();

        // selectionne la feuille courante
        $sheet = $spreadsheet->getActiveSheet();

        // met le nom à la feuille
        $sheet->setTitle($this->stringService->truncate($project->getName(), 31));

        // Création des entêtes
        $spreadsheet = $this->headersProjectAidsSpreadSheetExport($spreadsheet);

        // Ajout des données
        $spreadsheet = $this->dataProjectAidsSpreadSheetExport($spreadsheet, $project);

        return $spreadsheet;
    }

    private function headersProjectAidsSpreadSheetExport(
        Spreadsheet $spreadsheet,
        int $row = 1
    ): Spreadsheet {
        $headers = [
            'Adresse de la fiche aide',
            'Nom',
            'Description complète de l’aide et de ses objectifs',
            'Exemples de projets réalisables',
            'État d’avancement du projet pour bénéficier du dispositif',
            'Types d’aide',
            'Types de dépenses / actions couvertes',
            'Date d’ouverture',
            'Date de clôture',
            'Taux de subvention, min. et max. (en %, nombre entier)',
            'Taux de subvention (commentaire optionnel)',
            'Montant de l’avance récupérable',
            'Montant du prêt maximum',
            'Autre aide financière (commentaire optionnel)',
            'Contact',
            'Récurrence',
            'Appel à projet / Manifestation d’intérêt',
            'Sous-thématiques',
            'Porteurs d’aides',
            'Instructeurs',
            'Programmes',
            'Périmètre de l\'aide'
        ];

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A' . $row);

        return $spreadsheet;
    }

    private function dataProjectAidsSpreadSheetExport(
        Spreadsheet $spreadsheet,
        Project $project,
        int $startRow = 2
    ): Spreadsheet {
        $row = $startRow;

        foreach ($project->getAidProjects() as $aidProject) {
            if (!$aidProject->getAid() instanceof Aid) {
                continue;
            }

            $aidSteps = [];
            foreach ($aidProject->getAid()->getAidSteps() as $aidStep) {
                $aidSteps[] = $aidStep->getName();
            }
            $aidTypes = [];
            foreach ($aidProject->getAid()->getAidTypes() as $aidType) {
                $aidTypes[] = $aidType->getName();
            }
            $aidDestinations = [];
            foreach ($aidProject->getAid()->getAidDestinations() as $aidDestination) {
                $aidDestinations[] = $aidDestination->getName();
            }
            $dateStart = $aidProject->getAid()->getDateStart()
                ? $aidProject->getAid()->getDateStart()->format('Y-m-d')
                : '';
            $dateSubmissionDeadline = $aidProject->getAid()->getDateSubmissionDeadline()
                ? $aidProject->getAid()->getDateSubmissionDeadline()->format('Y-m-d')
                : '';
            $rates = '';
            if ($aidProject->getAid()->getSubventionRateMin()) {
                $rates .= ' Min : ' . $aidProject->getAid()->getSubventionRateMin();
            }
            if ($aidProject->getAid()->getSubventionRateMax()) {
                $rates .= ' Max : ' . $aidProject->getAid()->getSubventionRateMax();
            }
            $categories = [];
            foreach ($aidProject->getAid()->getCategories() as $aidCategory) {
                $categories[] = $aidCategory->getName();
            }
            $aidRecurrence = $aidProject->getAid()->getAidRecurrence()
                ? $aidProject->getAid()->getAidRecurrence()->getName()
                : '';
            $financers = [];
            foreach ($aidProject->getAid()->getAidFinancers() as $aidFinancer) {
                $financers[] = $aidFinancer->getBacker()->getName();
            }
            $instructors = [];
            foreach ($aidProject->getAid()->getAidInstructors() as $aidInstructor) {
                $instructors[] = $aidInstructor->getBacker()->getName();
            }
            $programs = [];
            foreach ($aidProject->getAid()->getPrograms() as $aidProgram) {
                $programs[] = $aidProgram->getName();
            }

            $aid = $aidProject->getAid();

            // Nettoyer le HTML et conserver les sauts de ligne
            $description = $this->cleanHtml($aid->getDescription());
            $projectExamples = $this->cleanHtml($aid->getProjectExamples());
            $subventionComment = $this->cleanHtml($aid->getSubventionComment());
            $otherFinancialAidComment = $this->cleanHtml($aid->getOtherFinancialAidComment());
            $contact = $this->cleanHtml($aid->getContact());

            $cells = [
                $aid->getUrl(),
                $aid->getName(),
                $description,
                $projectExamples,
                implode(',', $aidSteps),
                implode(',', $aidTypes),
                implode(',', $aidDestinations),
                $dateStart,
                $dateSubmissionDeadline,
                $rates,
                $subventionComment,
                $aid->getRecoverableAdvanceAmount() ?? '',
                $aid->getLoanAmount() ?? '',
                $otherFinancialAidComment,
                $contact,
                $aidRecurrence,
                $aid->isIsCallForProject() ? 'Oui' : 'Non',
                implode(',', $categories),
                implode(',', $financers),
                implode(',', $instructors),
                implode(',', $programs),
                $aid->getPerimeter() ? $aid->getPerimeter()->getName() : ''
            ];

            // Ajoute les datas à la feuille
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($cells, null, 'A' . $row);
            $row++;
        }

        return $spreadsheet;
    }

    /**
     * Exporter dans un fichier
     *
     * @param array<int, mixed> $results
     * @param string $entityFqcn
     * @param string $filename
     * @param string $format
     * @return string|null
     */
    public function exportToFile(
        array $results,
        string $entityFqcn,
        string $filename,
        string $format = FileService::FORMAT_CSV
    ): ?string {
        try {
            $entity = new $entityFqcn();
            $datas = $this->getDatasFromEntityType($entity, $results);
            $now = new \DateTime(date(self::TODAY_DATE_FORMAT));
            $tmpFolder = $this->fileService->getUploadTmpDir();
            if (!is_dir($tmpFolder)) {
                mkdir($tmpFolder, 0777, true);
            }
            $fileTarget = $tmpFolder
                . '/export_'
                . pathinfo($filename, PATHINFO_FILENAME)
                . '_at_'
                . $now->format('d_m_Y_H_i_s');
            if ($format == FileService::FORMAT_CSV) {
                $options = new \OpenSpout\Writer\CSV\Options();
                $options->FIELD_DELIMITER = ';';
                $options->FIELD_ENCLOSURE = '"';
                $fileTarget .= '.' . FileService::FORMAT_CSV;

                $writer = new \OpenSpout\Writer\CSV\Writer($options);
            } elseif ($format == FileService::FORMAT_XLSX) {
                $sheetView = new SheetView();
                $writer = new \OpenSpout\Writer\XLSX\Writer();
                $fileTarget .= '.' . FileService::FORMAT_XLSX;
            } else {
                throw new ExceptionInvalidFileFormatException(self::EXCEPTION_FORMAT_NOT_SUPPORTED_MESSAGE);
            }

            $writer->openToFile($fileTarget);

            if ($format == FileService::FORMAT_XLSX && isset($sheetView)) {
                $writer->getCurrentSheet()->setSheetView($sheetView);
            }
            $headers = [];
            if (isset($datas[0])) {
                $headers = array_keys($datas[0]);
            }

            $cells = [];
            foreach ($headers as $csvHeader) {
                $cells[] = Cell::fromValue($csvHeader);
            }

            /** add a row at a time */
            $singleRow = new Row($cells);
            $writer->addRow($singleRow);

            foreach ($datas as $data) {
                $cells = [];
                foreach ($data as $value) {
                    $cells[] = Cell::fromValue($value);
                }
                $singleRow = new Row($cells);
                $writer->addRow($singleRow);
            }

            $writer->close();
            return $fileTarget;
        } catch (\Exception $e) {
            $this->loggerInterface->error('Erreur exportToFile', [
                'exception' => $e
            ]);
            return null;
        }
    }

    public function truncateHtml(?string $html, int $length = 32767): ?string
    {
        if (!$html) {
            return null;
        }
        // Tronque le HTML à 32767 caractères
        $truncatedHtml = substr($html, 0, $length);

        // Assainit le HTML tronqué
        return $this->htmlSanitizerInterface->sanitize($truncatedHtml);
    }

    private function cleanHtml(?string $html): string
    {
        if (!$html) {
            return '';
        }

        // Supprimer toutes les balises HTML
        $text = strip_tags($html);

        // Décoder les entités HTML
        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));
    }
}
