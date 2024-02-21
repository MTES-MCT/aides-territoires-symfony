<?php

namespace App\Controller;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Blog\BlogPost;
use App\Entity\Blog\BlogPostCategory;
use App\Entity\Page\Page;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Project\Project;
use App\Entity\Search\SearchPage;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(priority:1)]
class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap')]
    public function index(
        ManagerRegistry $managerRegistry,
        StringService $stringService
    ): Response
    {
        /* fréquences possible :
         * always
         * hourly
         * daily
         * weekly
         * monthly
         * yearly
         * never
         */
        // priority : 0.2 / 0.5 / 0.8 / 1

        $urls = [];

        // ----------------------------------------------------------------------
        // PAGES STATIQUES

        // accueil
        $urls[] = [
            'loc' => $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'changefreq' => 'monthly',
            'priority' => 1
        ];

        // accessibilite
        $urls[] = [
            'loc' => $this->generateUrl('app_static_accessibility', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // API et réutilisations des données
        $urls[] = [
            'loc' => $this->generateUrl('app_static_data', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // mentions légales
        $urls[] = [
            'loc' => $this->generateUrl('app_static_legal_notice', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // politique de confidentialité
        $urls[] = [
            'loc' => $this->generateUrl('app_static_privacy_policy', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // stats
        $urls[] = [
            'loc' => $this->generateUrl('app_static_stats', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // Conditions générales d\'utilisation
        $urls[] = [
            'loc' => $this->generateUrl('app_static_terms', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // ----------------------------------------------------------------------
        // AIDES

        // index
        $urls[] = [
            'loc' => $this->generateUrl('app_aid_aid', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // détails des aides publiées
        $aids = $managerRegistry->getRepository(Aid::class)->findForSitemap([
            'showInSearch' => true
        ]);

        foreach ($aids as $aid) {
            $urls[] = [
                'loc' => $this->generateUrl('app_aid_aid_details', ['slug' => $aid['slug']], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }
        unset($aids);

        // ----------------------------------------------------------------------
        // PORTEURS D'AIDES

        // détails de chaque porteurs d'aides
        $backers = $managerRegistry->getRepository(Backer::class)->findAll();
        foreach ($backers as $backer) {
            $urls[] = [
                'loc' => $this->generateUrl('app_backer_details', [
                    'id' => $backer->getId(),
                    'slug' => $backer->getSlug()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }
        unset($backers);

        // ----------------------------------------------------------------------
        // BLOG

        // index
        $urls[] = [
            'loc' => $this->generateUrl('app_blog_blog', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // categories
        $blogPostCategories = $managerRegistry->getRepository(BlogPostCategory::class)->findAll();
        foreach ($blogPostCategories as $blogPostCategory) {
            $urls[] = [
                'loc' => $this->generateUrl('app_blog_blog_category', [
                    'slug' => $blogPostCategory->getSlug()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }
        unset($blogPostCategories);

        // posts
        $blogPosts = $managerRegistry->getRepository(BlogPost::class)->findBy(
            [
                'status' => BlogPost::STATUS_PUBLISHED
            ],
            [
                'datePublished' => 'DESC'
            ]
        );
        foreach ($blogPosts as $blogPost) {
            $urls[] = [
                'loc' => $this->generateUrl('app_blog_post_details', [
                    'slug' => $blogPost->getSlug()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }
        unset($blogPosts);

        // ----------------------------------------------------------------------
        // CARTOGRAPHIE

        // index
        $urls[] = [
            'loc' => $this->generateUrl('app_cartography_cartography', [
                'slug' => $blogPostCategory->getSlug()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // pages par départements
        $counties = $managerRegistry->getRepository(Perimeter::class)->findCounties();
        foreach ($counties as $county) {
            $urls[] = [
                'loc' => $this->generateUrl('app_cartography_detail', [
                    'code' => $county->getCode(),
                    'slug' => $stringService->getSlug($county->getName())
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }
        unset($counties);

        // ----------------------------------------------------------------------
        // CONTACT
        
        // page contact
        $urls[] = [
            'loc' => $this->generateUrl('app_contact_contact', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // ----------------------------------------------------------------------
        // PAGES

        // pages
        $pages = $managerRegistry->getRepository(Page::class)->findAll();
        foreach ($pages as $page) {
            $urls[] = [
                'loc' => $this->generateUrl('app_home', [
                ], UrlGeneratorInterface::ABSOLUTE_URL). trim($page->getUrl(), '/'). '/',
            ];
        }
        unset($pages);
        // ----------------------------------------------------------------------
        // PORTAILS

        $searchPages = $managerRegistry->getRepository(SearchPage::class)->findBy([

        ]);
        foreach ($searchPages as $searchPage) {
            $urls[] = [
                'loc' => $this->generateUrl('app_portal_portal_details', [
                    'slug' => $searchPage->getSlug()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }
        unset($searchPages);

        // ----------------------------------------------------------------------
        // PROGRAMMES

        // index
        $urls[] = [
            'loc' => $this->generateUrl('app_program_program', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // pages détails
        $programs = $managerRegistry->getRepository(Program::class)->findAll();
        foreach ($programs as $program) {
            $urls[] = [
                'loc' => $this->generateUrl('app_program_details', [
                    'slug' => $program->getSlug()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }
        unset($programs);

        // ----------------------------------------------------------------------
        // PROJETS

        // index projets publics
        $urls[] = [
            'loc' => $this->generateUrl('app_project_project_public', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        // pages détails projets publics
        $publicProjects = $managerRegistry->getRepository(Project::class)->findBy(
            [
                'status' => Project::STATUS_PUBLISHED,
                'isPublic' => true
            ]
        );
        foreach ($publicProjects as $publicProject) {
            $urls[] = [
                'loc' => $this->generateUrl('app_project_project_public_details', [
                    'id' => $publicProject->getId(),
                    'slug' => $publicProject->getSlug()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }
        unset($publicProjects);
        
        // ----------------------------------------------------------------------
        // Rendu template
        $response = new Response(
            $this->renderView(
                'sitemap.html.twig', array(
                'urls'          => $urls
            )),
            200,
            array(
                'Content-Type' => 'text/xml'
            )
        );
        return $response;
    }
}
