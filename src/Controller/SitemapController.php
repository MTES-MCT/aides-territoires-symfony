<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap')]
    public function index(): Response
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
        // Accueil
        $urls[] = [
            'loc' => $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'changefreq' => 'monthly',
            'priority' => 1
        ];

        // ----------------------------------------------------------------------
        // Blog - Articles
        // $blogPosts = $entityManagerInterface->getRepository(BlogPost::class)->findBy(
        //     [
        //         'active' => 1,
        //     ],
        //     [
        //         'datePublish' => 'desc'
        //     ]
        // );
        // foreach ($blogPosts as $blogPost) {
        //     $urls[] = [
        //         'loc' => $this->generateUrl('blog_post_details', [
        //             'slug' => $blogPost->getSLug(),
        //             '_locale' => $blogPost->getLanguage()->getIso()
        //         ], UrlGeneratorInterface::ABSOLUTE_URL),
        //         'changefreq' => 'monthly',
        //         'priority' => 1
        //     ];
        // }

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
