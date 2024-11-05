<?php

namespace App\Controller\Admin\Statistics;

use App\Entity\Log\LogAidSearch;
use App\Entity\Perimeter\Perimeter;
use App\Form\Admin\Filter\DateRangeType;
use App\Repository\Log\LogAidSearchRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

class LogAidSearchController extends AbstractController
{
    public function __construct(
        private LogAidSearchRepository $logAidSearchRepository,
        private RequestStack $requestStack
    ) {
    }

    #[Route('/admin/statistics/log/aid-search', name: 'admin_statistics_log_aid_search')]
    public function blogDashboard(
        AdminContext $adminContext,
        LogAidSearchRepository $logAidSearchRepository,
        ProjectReferenceRepository $projectReferenceRepository
    ): Response {
        // dates par défaut
        $dateMin = new \DateTime('-1 week');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class);
        $formDateRange->handleRequest($adminContext->getRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        // les recherches qui donnent peu de résultats
        $logAidSearchs = $logAidSearchRepository->findKeywordSearchWithFewResults([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax,
            'hasSearch' => true,
            'resultsCountMax' => 10,
            'orderBy' => [
                'sort' => 'l.timeCreate',
                'order' => 'DESC'
            ]
        ]);

        $queriesByLogId = [];
        /** @var LogAidSearch $logAidSearch */
        foreach ($logAidSearchs as $logAidSearch) {
            $queriesByLogId[$logAidSearch->getId()] = explode('&', $logAidSearch->getQuerystring());
        }

        // regarde si il y a un projet référent correspondant à la recherche
        $projectReferences = $projectReferenceRepository->findAll();
        $projectReferencesByLogId = [];
        foreach ($logAidSearchs as $logAidSearch) {
            $projectReferencesByLogId[$logAidSearch->getId()] = null;
            foreach ($projectReferences as $projectReference) {
                if ($projectReference->getName() == $logAidSearch->getSearch()) {
                    $projectReferencesByLogId[$logAidSearch->getId()] = $projectReference;
                    break;
                }
            }
        }

        return $this->render('admin/statistics/log/aid-search.html.twig', [
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'logAidSearchs' => $logAidSearchs,
            'queriesByLogId' => $queriesByLogId,
            'projectReferencesByLogId' => $projectReferencesByLogId
        ]);
    }


    #[Route(
        '/admin/statistics/log/aid-search/missing-perimeters',
        name: 'admin_statistics_log_aid_search_missing_perimeters'
    )]
    public function missingPerimeters(
        LogAidSearchRepository $logAidSearchRepository,
        AdminContext $adminContext,
        ChartBuilderInterface $chartBuilderInterface,
        PerimeterRepository $perimeterRepository
    ): Response {
        // dates par défaut
        $dateMin = new \DateTime('-1 week');
        $dateMax = new \DateTime();

        // formulaire de filtre
        $formDateRange = $this->createForm(DateRangeType::class);
        $formDateRange->handleRequest($adminContext->getRequest());
        if ($formDateRange->isSubmitted()) {
            if ($formDateRange->isValid()) {
                $dateMin = $formDateRange->get('dateMin')->getData();
                $dateMax = $formDateRange->get('dateMax')->getData();
            }
        } else {
            $formDateRange->get('dateMin')->setData($dateMin);
            $formDateRange->get('dateMax')->setData($dateMax);
        }

        // les départements (pour affichage)
        $departments = $perimeterRepository->getDepartments();

        $departmentsByCode = [];
        $logAidSearchsByDept = [];
        foreach ($departments as $department) {
            $departmentsByCode[(string) $department->getCode()] = $department;
            $logAidSearchsByDept[(string) $department->getCode()] = [
                'dept' => (string) $department->getCode(),
                'count' => 0,
                'fullName' => $department->getName(),
                'class' => 'none'
            ];
        }

        // les stats
        $logAidSearchs = $logAidSearchRepository->getSearchOnPerimeterWithoutOrganization([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax
        ]);


        foreach ($logAidSearchs as $logAidSearch) {
            if (!$logAidSearch['insee']) {
                // on recherche le département dans les parents
                $perimeter = $perimeterRepository->findOneBy(['id' => $logAidSearch['id']]);
                if ($perimeter) {
                    foreach ($perimeter->getPerimetersTo() as $perimeterTo) {
                        if ($perimeterTo->getScale() == Perimeter::SCALE_DEPARTEMENT) {
                            $logAidSearch['insee'] = $perimeterTo->getCode();
                            break;
                        }
                    }
                }
            }

            // on met les stats par département
            $dept = ($logAidSearch['insee']) ? substr($logAidSearch['insee'], 0, 2) : 0;
            if ($dept >= 97) {
                $dept = substr($logAidSearch['insee'], 0, 3);
            }
            $logAidSearchsByDept[$dept]['count']++;
        }

        if (!empty($logAidSearchsByDept)) {
            // on reparcours pour récupérer le count max et en déduire des quartiles
            $countMax = 0;
            foreach ($logAidSearchsByDept as $byDept) {
                if ($byDept['count'] > $countMax) {
                    $countMax = $byDept['count'];
                }
            }
            $medium = (int) round($countMax / 2, 0);
            $first = (int) floor($medium / 2);
            $last = $medium + $first;

            // on reparcours pour attribuer une clase à chaque dept
            foreach ($logAidSearchsByDept as $key => $byDept) {
                if ($byDept['count'] == 0) {
                    continue;
                }
                if ($byDept['count'] <= $first) {
                    $logAidSearchsByDept[$key]['class'] = 'first';
                } elseif ($byDept['count'] <= $medium) {
                    $logAidSearchsByDept[$key]['class'] = 'medium';
                } elseif ($byDept['count'] <= $last) {
                    $logAidSearchsByDept[$key]['class'] = 'last';
                } else {
                    $logAidSearchsByDept[$key]['class'] = 'more';
                }
            }
        }

        // rendu template
        return $this->render('admin/statistics/log/aid-search-missing-perimeters.html.twig', [
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'logAidSearchsByDept' => $logAidSearchsByDept,
            'first' => $first ?? 0,
            'medium' => $medium ?? 0,
            'last' => $last ?? 0
        ]);
    }

    #[Route(
        '/admin/statistics/log/aid-search/missing-perimeters/export',
        name: 'admin_statistics_log_aid_search_missing_perimeters_export'
    )]
    public function exportRegistrationByMonth(): StreamedResponse
    {
        $response = new StreamedResponse();
        $response->setCallback(function () {

            $dateMin = $this->requestStack->getCurrentRequest()->get('dateMin')
                ? new \DateTime($this->requestStack->getCurrentRequest()->get('dateMin'))
                : new \DateTime(date('Y-m-d', strtotime('-1 week')));
            $dateMax = $this->requestStack->getCurrentRequest()->get('dateMax')
                ? new \DateTime($this->requestStack->getCurrentRequest()->get('dateMax'))
                : new \DateTime(date('Y-m-d'));

            // options CSV
            $options = new \OpenSpout\Writer\CSV\Options();
            $options->FIELD_DELIMITER = ';';
            $options->FIELD_ENCLOSURE = '"';

            // writer
            $writer = new \OpenSpout\Writer\CSV\Writer($options);

            // ouverture fichier
            $now = new \DateTime(date('Y-m-d H:i:s'));
            $writer->openToBrowser('export_recherche_perimetre_sans_organisation_' . $now->format('d_m_Y') . '.csv');

            // entêtes
            $cells = [
                Cell::fromValue('Id périmètre'),
                Cell::fromValue('Périmètre'),
                Cell::fromValue('Code insee'),
            ];
            $singleRow = new Row($cells);
            $writer->addRow($singleRow);

            // les inscriptions
            $logAidSearchs = $this->logAidSearchRepository->getSearchOnPerimeterWithoutOrganization([
                'dateCreateMin' => $dateMin,
                'dateCreateMax' => $dateMax
            ]);
            foreach ($logAidSearchs as $logAidSearch) {
                // ajoute ligne par ligne
                $cells = [
                    Cell::fromValue($logAidSearch['id']),
                    Cell::fromValue($logAidSearch['name']),
                    Cell::fromValue($logAidSearch['insee'])
                ];

                $singleRow = new Row($cells);
                $writer->addRow($singleRow);
            }

            // fermeture fichier
            $writer->close();
        });

        return $response;
    }
}
