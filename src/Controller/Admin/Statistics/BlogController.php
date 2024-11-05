<?php

namespace App\Controller\Admin\Statistics;

use App\Controller\Admin\DashboardController;
use App\Entity\Blog\BlogPost;
use App\Entity\Blog\BlogPostCategory;
use App\Form\Admin\Filter\DateRangeType;
use App\Repository\Log\LogBlogPostViewRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class BlogController extends DashboardController
{
    #[Route('/admin/statistics/blog/dashboard', name: 'admin_statistics_blog_dashboard')]
    public function blogDashboard(
        AdminContext $adminContext,
        LogBlogPostViewRepository $logBlogPostViewRepository,
        FormFactoryInterface $formFactoryInterface,
        ChartBuilderInterface $chartBuilderInterface
    ): Response {
        // dates par défaut
        $dateMin = new \DateTime('-1 month');
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

        // top articles
        $topBlogPosts = $logBlogPostViewRepository->findTopOfDateRange([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        // top categories
        $topBlogPostsCategories = $logBlogPostViewRepository->findTopCategoriesOfDateRange([
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
        ]);

        $chartTopCategories = $chartBuilderInterface->createChart(Chart::TYPE_PIE);

        // première boucle pour faire les pourcentages
        $total = 0;
        foreach ($topBlogPostsCategories as $category) {
            $total += $category['nb'];
        }
        foreach ($topBlogPostsCategories as $key => $category) {
            $topBlogPostsCategories[$key]['percentage'] = $total == 0
                ? 0
                : number_format(($category['nb'] * 100 / $total), 2);
        }

        $labels = [];
        $datas = [];
        $categories = [];
        foreach ($topBlogPostsCategories as $category) {
            $labels[] = $category['blogPostCategory']->getName() . ' (' . $category['percentage'] . '%)';
            $datas[] = $category['nb'];
            $categories[] = $category['blogPostCategory'];
        }
        $colors = $this->getCategoriesBackgroundColor($categories);

        $chartTopCategories->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nombre de vues',
                    'backgroundColor' => $colors,
                    'data' => $datas,
                ],
            ],
        ]);


        // formulaire de filtre evolution
        // dates par défaut
        $dateMinEvolution = new \DateTime('-1 month');
        $dateMaxEvolution = new \DateTime();


        $formDateRangeEvolution = $formFactoryInterface->createNamed(
            'date_range_evolution',
            DateRangeType::class,
            null,
            [
                'action' => $this->adminUrlGenerator->setRoute('admin_statistics_blog_dashboard')->generateUrl()
                . '#evolution',
            ]
        );
        $formDateRangeEvolution->add('blogPost', EntityType::class, [
            'required' => true,
            'class' => BlogPost::class,
            'choice_label' => 'name',
            'label' => 'Article',
            'placeholder' => 'Sélectionnez un article',
            'query_builder' => function ($er) {
                return $er->createQueryBuilder('bp')
                    ->orderBy('bp.datePublished', 'DESC');
            },
            'autocomplete' => true,
        ]);
        $formDateRangeEvolution->handleRequest($adminContext->getRequest());
        if ($formDateRangeEvolution->isSubmitted()) {
            if ($formDateRangeEvolution->isValid()) {
                $dateMinEvolution = $formDateRangeEvolution->get('dateMin')->getData();
                $dateMaxEvolution = $formDateRangeEvolution->get('dateMax')->getData();

                // les vues
                $views = $logBlogPostViewRepository->countByDate([
                    'dateMin' => $dateMinEvolution,
                    'dateMax' => $dateMaxEvolution,
                    'blogPost' => $formDateRangeEvolution->get('blogPost')->getData(),
                ]);

                // on transforme en tableau par dates
                $viewByDate = [];
                foreach ($views as $view) {
                    $viewByDate[$view['dateCreate']->format('Y-m-d')] = [
                        'date' => $view['dateCreate']->format('Y-m-d'),
                        'nb' => $view['nb']
                    ];
                }

                // on refait le tableau en prenant toutes les dates
                $viewsByDateFinal = [];
                $dateStart = clone $dateMinEvolution;
                while ($dateStart <= $dateMaxEvolution) {
                    $date = $dateStart->format('Y-m-d');
                    $viewsByDateFinal[$date] = [
                        'date' => $date,
                        'nb' => $viewByDate[$date]['nb'] ?? 0
                    ];
                    $dateStart->modify('+1 day');
                }

                $chartEvolution = $chartBuilderInterface->createChart(Chart::TYPE_LINE);

                $labels = [];
                $datas = [];
                foreach ($viewsByDateFinal as $view) {
                    $labels[] = $view['date'];
                    $datas[] = $view['nb'];
                }
                $chartEvolution->setData([
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Nombre de vues',
                            'backgroundColor' => 'rgb(255, 99, 132)',
                            'borderColor' => 'rgb(255, 99, 132)',
                            'data' => $datas,
                        ],
                    ],
                ]);

                $chartEvolution->setOptions([
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                        ],
                    ],
                ]);
            }
        } else {
            $formDateRangeEvolution->get('dateMin')->setData($dateMinEvolution);
            $formDateRangeEvolution->get('dateMax')->setData($dateMaxEvolution);
        }


        return $this->render('admin/statistics/blog/dashboard.html.twig', [
            'formDateRange' => $formDateRange,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax,
            'topBlogPosts' => $topBlogPosts,
            'chartTopCategories' => $chartTopCategories,
            'formDateRangeEvolution' => $formDateRangeEvolution,
            'dateMinEvolution' => $dateMinEvolution,
            'dateMaxEvolution' => $dateMaxEvolution,
            'chartEvolution' => $chartEvolution ?? null,
        ]);
    }

    /**
     *
     * @param array<int, BlogPostCategory> $array
     * @return string[]
     */
    private function getCategoriesBackgroundColor(array $array): array
    {
        $colorsBySlug = [
            'webinaires' => 'rgb(255, 99, 132)',
            'article' => 'rgb(54, 162, 235)',
            'outre-mer' => 'rgb(255, 205, 86)',
            'mobilite' => 'rgb(75, 192, 192)',
            'nouvelle-fonctionnalite' => 'rgb(153, 102, 255)',
            'fonds-vert' => 'rgb(255, 159, 64)',
            'europe' => 'rgb(201, 203, 207)',
            'default' => 'rgb(75, 192, 192)',
        ];
        $returnColors = [];

        foreach ($array as $blogPostCategory) {
            if (!isset($colorsBySlug[$blogPostCategory->getSlug()])) {
                $returnColors[] = $colorsBySlug['default'];
            } else {
                $returnColors[] = $colorsBySlug[$blogPostCategory->getSlug()];
            }
        }

        return $returnColors;
    }
}
