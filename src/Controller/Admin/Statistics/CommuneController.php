<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Aid\AidProject;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class CommuneController extends AbstractController
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ChartBuilderInterface $chartBuilderInterface,
    )
    {   
    }

    #[Route('/admin/statistics/commune/dashboard', name: 'admin_statistics_commune_dashboard')]
    public function communeDashboard(
    ): Response
    {
        // compte les inscriptions de commune mois par mois
        $communeRegistrationsByMonth = $this->managerRegistry->getRepository(Organization::class)->countRegistrationsByMonth([
            'typeSlug' =>  OrganizationType::SLUG_COMMUNE,
            'perimeterScale' => Perimeter::SCALE_COMMUNE,
        ]);
        $labels = [];
        $datas = [];
        foreach ($communeRegistrationsByMonth as $registration) {
            $labels[] = $registration['month'];
            $datas[] = $registration['nb'];
        }
        $chartRegistrationByMonth = $this->chartBuilderInterface->createChart(Chart::TYPE_LINE);

        $chartRegistrationByMonth->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Inscriptions',
                    'backgroundColor' => 'rgb(255, 255, 255)',
                    'borderColor' => 'rgb(255, 0, 0)',
                    'data' => $datas,
                ],
            ],
        ]);
        $chartRegistrationByMonth->setOptions([
            'maintainAspectRatio' => true,
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Evolution de l\'inscription des communes mois par mois',
                    'font' => [
                        'size' => 24,
                    ],
                ],
            ],
        ]);

        // le nombre de commune (périmètre) ayant X organizations (par nb organiztion)
        $nbPerimeterByNbOrganization = $this->managerRegistry->getRepository(Perimeter::class)->countNbByOrganization(
            [
                'scalePerimeter' => Perimeter::SCALE_COMMUNE,
                // 'organizationTypeSlug' => OrganizationType::SLUG_COMMUNE,
            ]
        );
        $reducedArray = [];
        $reducedArray['10+'] = [
            'nb_organization' => '10+',
            'nb_perimeter' => 0,
        ];
        foreach ($nbPerimeterByNbOrganization as $key => $nbPerimeter) {
            if ($nbPerimeter['nb_organization'] < 10) {
                $reducedArray[$nbPerimeter['nb_organization']] = $nbPerimeter;
            } else {
                $reducedArray['10+']['nb_perimeter'] += $nbPerimeter['nb_perimeter'];
            }
        }
        // Supprime l'élément avec la clé "10+" du tableau
        $tenPlus = $reducedArray['10+'];
        unset($reducedArray['10+']);

        // Ajoute l'élément à la fin du tableau
        $reducedArray['10+'] = $tenPlus;
        
        // première boucle pour faire les pourcentages
        $total = 0;
        foreach ($reducedArray as $key => $nbPerimeter) {
            $total += (int) $nbPerimeter['nb_perimeter'];
        }
        $labels = [];
        $datas = [];
        $colors = [];
        $nbPerimeterTotal = 0;
        foreach ($reducedArray as $nbPerimeter) {
            $percentage = $total == 0 ? 0 : number_format(($nbPerimeter['nb_perimeter'] * 100 / $total), 2);
            $labels[] = $nbPerimeter['nb_perimeter'] . ' commune(s) ont '.$nbPerimeter['nb_organization']. ' structure(s) (de tous type) : ('.$percentage.'%)';
            $datas[] = $nbPerimeter['nb_perimeter'];
            $colors[] = 'rgb('.rand(0, 255).', '.rand(0, 255).', '.rand(0, 255).')';
            $nbPerimeterTotal += $nbPerimeter['nb_perimeter'];
        }

        // graphique
        $chartNbPerimeterByNbOrganization = $this->chartBuilderInterface->createChart(Chart::TYPE_PIE);
        $chartNbPerimeterByNbOrganization->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nombre de communes (périmètre) par nombre de structures',
                    'backgroundColor' => $colors,
                    'data' => $datas,
                ],
            ],
        ]);

        $chartNbPerimeterByNbOrganization->setOptions([
            'maintainAspectRatio' => true,
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Répartition des '.$nbPerimeterTotal.' communes par nombre de structures liées',
                    'font' => [
                        'size' => 24,
                    ],
                ],
            ],
        ]);

        // Nombre d'aides associés à des projets par des communes (par mois)
        $nbApCreatedByMonth = $this->managerRegistry->getRepository(AidProject::class)->countCreatedByMonth(
            [
                'organizationTypeSlug' => OrganizationType::SLUG_COMMUNE
            ]
        );
        $labels = [];
        $datas = [];
        foreach ($nbApCreatedByMonth as $creation) {
            $labels[] = $creation['mois'];
            $datas[] = $creation['nb'];
        }
        $chartNbApByMonth = $this->chartBuilderInterface->createChart(Chart::TYPE_LINE);

        $chartNbApByMonth->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nombre d\'aides ajoutées',
                    'backgroundColor' => 'rgb(255, 255, 255)',
                    'borderColor' => 'rgb(255, 0, 0)',
                    'data' => $datas,
                ],
            ],
        ]);
        $chartNbApByMonth->setOptions([
            'maintainAspectRatio' => true,
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Evolution du nombre d\'aides ajoutées à des projets par des communes mois par mois',
                    'font' => [
                        'size' => 24,
                    ],
                ],
            ],
        ]);

        // retour template
        return $this->render('admin/statistics/commune/dashboard.html.twig', [
            'chartRegistrationByMonth' => $chartRegistrationByMonth,
            'chartNbPerimeterByNbOrganization' => $chartNbPerimeterByNbOrganization,
            'chartNbApByMonth' => $chartNbApByMonth
        ]);
    }

    #[Route('/admin/statistics/commune/export/registration-by-month', name: 'admin_statistics_commune_export_registration_by_month')]
    public function exportRegistrationByMonth(
    ): StreamedResponse
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');

        $response = new StreamedResponse();
        $response->setCallback(function () {
                    // options CSV
        $options = new \OpenSpout\Writer\CSV\Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';

        // writer
        $writer = new \OpenSpout\Writer\CSV\Writer($options);

        // ouverture fichier
        $now = new \DateTime(date('Y-m-d H:i:s'));
        $writer->openToBrowser('export_inscriptions_communes_at_'.$now->format('d_m_Y').'.csv');

        // entêtes
        $cells = [
            Cell::fromValue('Mois'),
            Cell::fromValue('Nombre inscription commune'),
        ];
        $singleRow = new Row($cells);
        $writer->addRow($singleRow);

        // les inscriptions
        $communeRegistrationsByMonth = $this->managerRegistry->getRepository(Organization::class)->countRegistrationsByMonth([
            'typeSlug' =>  OrganizationType::SLUG_COMMUNE,
            'perimeterScale' => Perimeter::SCALE_COMMUNE,
        ]);
        foreach ($communeRegistrationsByMonth as $registration) {
            // ajoute ligne par ligne
            $cells = [
                Cell::fromValue($registration['month']),
                Cell::fromValue($registration['nb'])
            ];

            $singleRow = new Row($cells);
            $writer->addRow($singleRow);
        }

        // fermeture fichier
        $writer->close();
        });

        return $response;
    }

    #[Route('/admin/statistics/commune/export/nb-ap-by-month', name: 'admin_statistics_commune_export_nb_ap_by_month')]
    public function exportNbApByMonth(
    ): StreamedResponse
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');

        $response = new StreamedResponse();
        $response->setCallback(function () {
                    // options CSV
        $options = new \OpenSpout\Writer\CSV\Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';

        // writer
        $writer = new \OpenSpout\Writer\CSV\Writer($options);

        // ouverture fichier
        $now = new \DateTime(date('Y-m-d H:i:s'));
        $writer->openToBrowser('export_aide_ajoute_projet_communes_at_'.$now->format('d_m_Y').'.csv');

        // entêtes
        $cells = [
            Cell::fromValue('Mois'),
            Cell::fromValue('Nombre d\'aides ajoutées à des projets'),
        ];
        $singleRow = new Row($cells);
        $writer->addRow($singleRow);

        // les inscriptions
        $nbApCreatedByMonth = $this->managerRegistry->getRepository(AidProject::class)->countCreatedByMonth(
            [
                'organizationTypeSlug' => OrganizationType::SLUG_COMMUNE
            ]
        );
        foreach ($nbApCreatedByMonth as $nbCreation) {
            // ajoute ligne par ligne
            $cells = [
                Cell::fromValue($nbCreation['mois']),
                Cell::fromValue($nbCreation['nb'])
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