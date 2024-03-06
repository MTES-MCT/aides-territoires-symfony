<?php

namespace App\Service\Export;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Cron\CronExportSpreadsheet;
use App\Entity\Project\Project;
use App\Entity\User\User;
use App\Service\File\FileService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig\Environment;

class SpreadsheetExporterService
{
    public function __construct(
        protected Environment $twig,
        protected UserService $userService,
        protected ManagerRegistry $managerRegistry,
        protected FileService $fileService,
        protected HtmlSanitizerInterface $htmlSanitizerInterface
    )
    {
        
    }
    public function  createResponseFromQueryBuilder(
        QueryBuilder $queryBuilder,
        mixed $entityFcqn,
        string $filename,
        string $format = FileService::FORMAT_CSV
    ): StreamedResponse|Response
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');

        // compte le nombre de résultats
        $queryBuilderCount = clone $queryBuilder;
        $count = $queryBuilderCount->select('COUNT(entity)')->getQuery()->getSingleScalarResult();

        // si trop de résultats, on va traiter par cron
        if ($count > 1000) {
            $params = $queryBuilder->getQuery()->getParameters() ?? null;
            $sqlParams = [];
            if ($params) {
                foreach ($params as $param) {
                    if ($param->getValue() instanceof ArrayCollection) {
                        $values = [];
                        foreach ($param->getValue() as $value) {
                            $values[] = $value->getId();
                        }
                        $sqlParams[] = ['name' => $param->getName(), 'value' => $values];
                    } else {
                        $sqlParams[] = ['name' => $param->getName(), 'value' => $param->getValue()];
                    }
                }
            }

            $cronExportSpreadsheet = new CronExportSpreadsheet();
            $cronExportSpreadsheet->setSqlRequest($queryBuilder->getQuery()->getDQL());
            $cronExportSpreadsheet->setSqlParams($sqlParams);
            $cronExportSpreadsheet->setEntityFqcn($entityFcqn);
            $cronExportSpreadsheet->setFilename($filename.'.'.$format);
            $cronExportSpreadsheet->setFormat($format);
            $cronExportSpreadsheet->setUser($this->userService->getUserLogged());

            $this->managerRegistry->getManager()->persist($cronExportSpreadsheet);
            $this->managerRegistry->getManager()->flush();

            $content = $this->twig->render('admin/cron/cron-launched.html.twig', [
                
            ]);
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
            } else if ($format == FileService::FORMAT_XLSX) {
                $sheetView = new SheetView();               
                $writer = new \OpenSpout\Writer\XLSX\Writer();
            } else {
                throw new \Exception('Format not supported');
            }

            $now = new \DateTime(date('Y-m-d H:i:s'));
            $writer->openToBrowser('export_'.$filename.'_at_'.$now->format('d_m_Y').'.'.$format);

            if ($format == FileService::FORMAT_XLSX) {
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

    public function getDatasFromEntityType(mixed $entity, mixed $results): array
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
                        $rate .= ' Min : '.$result->getSubventionRateMin();
                    }
                    if ($result->getSubventionRateMax()) {
                        $rate .= ' Max : '.$result->getSubventionRateMax();
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
                        'Date de clôture' => $result->getDateSubmissionDeadline() ? $result->getDateSubmissionDeadline()->format('d/m/Y') : '',
                        
                        'Conditions d\'éligibilité' => $this->truncateHtml($result->getEligibility()), 
                        'État d\'avancement du projet pour bénéficier du dispositif' => join("\n", $aidSteps),
                        'Types de dépenses / actions couvertes' => join("\n", $destinations),
                        'Zone géographique couverte par l\'aide*' => $result->getPerimeter() ? $result->getPerimeter()->getName() : '',

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
                /** @var User $result */
                foreach ($results as $key => $result) {
                    $projectsHaveAids = false;
                    if ($result->getDefaultOrganization() && $result->getDefaultOrganization()->getProjects()) {
                        foreach ($result->getDefaultOrganization()->getProjects() as $project) {
                            if ($project->getAidProjects()) {
                                $projectsHaveAids = true;
                                break;
                            }
                        }
                    }
                    $datas[] = [
                        'id' => $result->getId(),
                        'Prénom' => $result->getFirstname(),
                        'Nom' => $result->getLastname(),
                        'Adresse e-mail' => $result->getEmail(),
                        'Numéro de téléphone' => $result->getContributorContactPhone(),
                        'Périmètre de l\'organization' => ($result->getDefaultOrganization() && $result->getDefaultOrganization()->getPerimeter()) ? $result->getDefaultOrganization()->getPerimeter()->getName() : '', 
                        'Périmètre (region)' => ($result->getDefaultOrganization() && $result->getDefaultOrganization()->getPerimeterRegion()) ? $result->getDefaultOrganization()->getPerimeterRegion()->getName() : '', 
                        'Périmètre (Département)' => ($result->getDefaultOrganization() && $result->getDefaultOrganization()->getPerimeterDepartment()) ? $result->getDefaultOrganization()->getPerimeterDepartment()->getName() : '', 
                        'Périmètre (Population)' => ($result->getDefaultOrganization() && $result->getDefaultOrganization()->getPerimeter()) ? $result->getDefaultOrganization()->getPerimeterDepartment()->getPopulation() : '', 
                        'Contributeur ?' => $result->isIsContributor() ? 'Oui' : 'Non',
                        'Bénéficiaire ?' => $result->isIsBeneficiary() ? 'Oui' : 'Non',
                        'Nombre d\'aides' => $result->getAids() ? count($result->getAids()) : 0,
                        'Structure du bénéficiaire' => $result->getDefaultOrganization() ? $result->getDefaultOrganization()->getName() : '',
                        'ID de l\'organisation' => $result->getDefaultOrganization() ? $result->getDefaultOrganization()->getId() : '',
                        'Nombre de projets de l\'organisation' => ($result->getDefaultOrganization() && $result->getDefaultOrganization()->getProjects()) ? count($result->getDefaultOrganization()->getProjects()) : 0,
                        'Présence d\'aides associées à un projet' => $projectsHaveAids ? 'VRAI' : '',
                        'Organisme (ancien champ)' => $result->getContributorOrganization(),
                        'Fonction du bénéficiaire' => $result->getBeneficiaryFunction(),
                        'Rôle (ancien champ)' => $result->getContributorRole(),
                        'Rôle du bénéficiaire' => $result->getBeneficiaryRole(),
                        'Date de création' => $result->getTimeCreate() ? $result->getTimeCreate()->format('Y-m-d H:i:s') : '',
                        'Date de mise à jour' => $result->getTimeUpdate() ? $result->getTimeUpdate()->format('Y-m-d H:i:s') : '',
                        'dernière connexion' => $result->getTimeLastLogin() ? $result->getTimeLastLogin()->format('Y-m-d H:i:s') : '',
                        'Type de structure' => ($result->getDefaultOrganization() && $result->getDefaultOrganization()->getOrganizationType()) ? $result->getDefaultOrganization()->getOrganizationType()->getName() : '',
                        'Code postal de la structure' => $result->getDefaultOrganization() ? $result->getDefaultOrganization()->getZipCode() : '',
                    ];
                    unset($results[$key]);
                }
                break;

                case Project::class:
                    /** @var Project $result */
                    foreach ($results as $key => $result) {
                        $regions = ($result->getOrganization() && $result->getOrganization()->getPerimeter()) ? $result->getOrganization()->getPerimeter()->getRegions() : '';
                        if (is_array($regions)) {
                            $regions = implode(',', $regions);
                        }
                        $counties = ($result->getOrganization() && $result->getOrganization()->getPerimeter()) ? $result->getOrganization()->getPerimeter()->getDepartments() : '';
                        if (is_array($counties)) {
                            $counties = implode(',', $counties);
                        }
                        $datas[] = [
                            'Nom' => $result->getName(),
                            'Organisation' => $result->getOrganization() ? $result->getOrganization()->getName() : '',
                            'Description' => $result->getDescription(),
                            'Périmètre du porteur de projet' => ($result->getOrganization() && $result->getOrganization()->getPerimeter()) ? $result->getOrganization()->getPerimeter()->getName() : '',
                            'Périmètre (Région)' => $regions,
                            'Périmètre (Département)' => $counties,
                            'Est public' => $result->isIsPublic() ? 'Oui' : 'Non',
                            'Date création' => $result->getTimeCreate() ? $result->getTimeCreate()->format('d/m/Y H:i:s') : '',
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
                        $aids = $this->managerRegistry->getRepository(Aid::class)->findCustom($aidsParams);
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
        }
        return $datas;
    }

    public function exportProjectAids(Project $project, string $format = 'csv')
    {
        try {
            if ($format == FileService::FORMAT_CSV) {
                $options = new \OpenSpout\Writer\CSV\Options();
                $options->FIELD_DELIMITER = ';';
                $options->FIELD_ENCLOSURE = '"';
    
                $writer = new \OpenSpout\Writer\CSV\Writer($options);
            } else if ($format == FileService::FORMAT_XLSX) {
                $sheetView = new SheetView();               
                $writer = new \OpenSpout\Writer\XLSX\Writer();
            } else {
                throw new \Exception('Format not supported');
            }

            $now = new \DateTime(date('Y-m-d H:i:s'));
            $writer->openToBrowser('Aides-territoires_-_'.$now->format('Y-m-d').'_-_'.$project->getSlug());

            if ($format == FileService::FORMAT_XLSX) {
                $writer->getCurrentSheet()->setSheetView($sheetView);
            }

            $cells = [
                Cell::fromValue('Adresse de la fiche aide'),
                Cell::fromValue('Nom'),
                Cell::fromValue('Description complète de l’aide et de ses objectifs'),
                Cell::fromValue('Exemples de projets réalisables'),
                Cell::fromValue('État d’avancement du projet pour bénéficier du dispositif'),
                Cell::fromValue('Types d’aide'),
                Cell::fromValue('Types de dépenses / actions couvertes'),
                Cell::fromValue('Date d’ouverture'),
                Cell::fromValue('Date de clôture'),
                Cell::fromValue('Taux de subvention, min. et max. (en %, nombre entier)'),
                Cell::fromValue('Taux de subvention (commentaire optionnel)'),
                Cell::fromValue('Montant de l’avance récupérable'),
                Cell::fromValue('Montant du prêt maximum'),
                Cell::fromValue('Autre aide financière (commentaire optionnel)'),
                Cell::fromValue('Contact'),
                Cell::fromValue('Récurrence'),
                Cell::fromValue('Appel à projet / Manifestation d’intérêt'),
                Cell::fromValue('Sous-thématiques'),
                Cell::fromValue('Porteurs d’aides'),
                Cell::fromValue('Instructeurs'),
                Cell::fromValue('Programmes'),
            ];
            
            /** add a row at a time */
            $singleRow = new Row($cells);
            $writer->addRow($singleRow);

            foreach ($project->getAidProjects() as $aidProject) {
                if (!$aidProject->getAid() instanceof Aid) {
                    continue;
                }
                // $projectStep = $project->getStep() && isset(Project::PROJECT_STEPS_BY_SLUG[$project->getStep()]) ? Project::PROJECT_STEPS_BY_SLUG[$project->getStep()] : '';
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
                $dateStart = $aidProject->getAid()->getDateStart() ? $aidProject->getAid()->getDateStart()->format('Y-m-d') : '';
                $dateSubmissionDeadline = $aidProject->getAid()->getDateSubmissionDeadline() ? $aidProject->getAid()->getDateSubmissionDeadline()->format('Y-m-d') : '';
                $rates = '';
                if ($aidProject->getAid()->getSubventionRateMin()) {
                    $rates .= ' Min : '.$aidProject->getAid()->getSubventionRateMin();
                }
                if ($aidProject->getAid()->getSubventionRateMax()) {
                    $rates .= ' Max : '.$aidProject->getAid()->getSubventionRateMax();
                }
                $categories = [];
                foreach ($aidProject->getAid()->getCategories() as $aidCategory) {
                    $categories[] = $aidCategory->getName();
                }
                $aidRecurrence = $aidProject->getAid()->getAidRecurrence() ? $aidProject->getAid()->getAidRecurrence()->getName() : '';
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
                $cells = [
                    Cell::fromValue($aidProject->getAid()->getUrl()),
                    Cell::fromValue($aidProject->getAid()->getName()),
                    Cell::fromValue($aidProject->getAid()->getDescription()),
                    Cell::fromValue($aidProject->getAid()->getProjectExamples()),
                    Cell::fromValue(implode(',', $aidSteps)),
                    Cell::fromValue(implode(',', $aidTypes)),
                    Cell::fromValue(implode(',', $aidDestinations)),
                    Cell::fromValue($dateStart),
                    Cell::fromValue($dateSubmissionDeadline),
                    Cell::fromValue($rates),
                    Cell::fromValue($aidProject->getAid()->getSubventionComment() ?? ''),
                    Cell::fromValue($aidProject->getAid()->getRecoverableAdvanceAmount() ?? ''),
                    Cell::fromValue($aidProject->getAid()->getLoanAmount() ?? ''),
                    Cell::fromValue($aidProject->getAid()->getOtherFinancialAidComment() ?? ''),
                    Cell::fromValue($aidProject->getAid()->getContact() ?? ''),
                    Cell::fromValue($aidRecurrence),
                    Cell::fromValue($aidProject->getAid()->isIsCallForProject() ? 'Oui' : 'Non'),
                    Cell::fromValue(implode(',', $categories)),
                    Cell::fromValue(implode(',', $financers)),
                    Cell::fromValue(implode(',', $instructors)),
                    Cell::fromValue(implode(',', $programs)),
                ];

                /** add a row at a time */
                $singleRow = new Row($cells);
                $writer->addRow($singleRow);
            }
            
            $writer->close();
            exit;
        } catch (\Exception $e) {
        }
    }

    public function exportToFile(array $results, string $entityFqcn, string $filename, string $format = FileService::FORMAT_CSV)
    {
        try {
            $entity = new $entityFqcn();
            $datas = $this->getDatasFromEntityType($entity, $results);
            $now = new \DateTime(date('Y-m-d H:i:s'));
            $tmpFolder = $this->fileService->getUploadTmpDir();
            if (!is_dir($tmpFolder)) {
                mkdir($tmpFolder, 0777, true);
            }
            $fileTarget = $tmpFolder.'/export_'.pathinfo($filename, PATHINFO_FILENAME).'_at_'.$now->format('d_m_Y_H_i_s');
            if ($format == FileService::FORMAT_CSV) {
                $options = new \OpenSpout\Writer\CSV\Options();
                $options->FIELD_DELIMITER = ';';
                $options->FIELD_ENCLOSURE = '"';
                $fileTarget .= '.'.FileService::FORMAT_CSV;
    
                $writer = new \OpenSpout\Writer\CSV\Writer($options);
            } else if ($format == FileService::FORMAT_XLSX) {
                $sheetView = new SheetView();               
                $writer = new \OpenSpout\Writer\XLSX\Writer();
                $fileTarget .= '.'.FileService::FORMAT_XLSX;
            } else {
                throw new \Exception('Format not supported');
            }
    
            $writer->openToFile($fileTarget);
    
            if ($format == FileService::FORMAT_XLSX) {
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
            return null;
        }
    }

    public function truncateHtml(?string $html, int $length = 32767) : ?string {
        if (!$html) {
            return null;
        }
        // Tronque le HTML à 32767 caractères
        $truncatedHtml = substr($html, 0, $length);

        // Assainit le HTML tronqué
        $cleanHtml = $this->htmlSanitizerInterface->sanitize($truncatedHtml);

        return $cleanHtml;
    }
}