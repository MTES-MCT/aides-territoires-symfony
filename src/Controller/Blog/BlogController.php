<?php

namespace App\Controller\Blog;

use App\Controller\FrontController;
use App\Entity\Blog\BlogPost;
use App\Entity\Blog\BlogPostCategory;
use App\Form\Blog\BlogPostCategoryFilterType;
use App\Repository\Blog\BlogPostCategoryRepository;
use App\Repository\Blog\BlogPostRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends FrontController
{
    const NB_POST_BY_PAGE = 18;

    #[Route('/blog/', name: 'app_blog_blog')]
    public function index(
        BlogPostRepository $blogPostRepository,
        RequestStack $requestStack
    ): Response
    {
        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);
        if (!$currentPage) {
            $currentPage = 1;
        }
        $nextPage = $currentPage + 1;
        $previousPage = $currentPage -1;

        // formulaire filtre catégories
        $formCategories = $this->createForm(BlogPostCategoryFilterType::class);
        $formCategories->handleRequest($requestStack->getCurrentRequest());
        if ($formCategories->isSubmitted()) {
            if ($formCategories->isValid()) {
                return $this->redirectToRoute('app_blog_blog_category', ['slug' => $formCategories->get('blogPostCategory')->getData()->getSlug()]);
            }
        }

        // les articles à afficher
        $blogPosts = $blogPostRepository->findBy(
            [
                'status' => BlogPost::STATUS_PUBLISHED
            ],
            [
                'datePublished' => 'DESC'
            ],
            self::NB_POST_BY_PAGE,
            ($currentPage - 1) * self::NB_POST_BY_PAGE
        );

        // le nombre d'article total
        $nbBlogPosts = $blogPostRepository->countCustom(
            [
                'status' => BlogPost::STATUS_PUBLISHED
            ]
        );

        // le nombre de page
        $nbPages = ceil($nbBlogPosts / self::NB_POST_BY_PAGE);

        // fil arianne
        $this->breadcrumb->add(
            'Blog',
            null
        );

        // rendu template
        return $this->render('blog/blogpost/index.html.twig', [
            'formCategories' => $formCategories->createView(),
            'blogPosts' => $blogPosts,
            'nbPages' => $nbPages,
            'currentPage' => $currentPage,
            'nextPage' => $nextPage,
            'previousPage' => $previousPage
        ]);
    }

    #[Route('/blog/categorie/{slug}', name: 'app_blog_blog_category', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function category(
        $slug,
        BlogPostCategoryRepository $blogPostCategoryRepository,
        BlogPostRepository $blogPostRepository,
        RequestStack $requestStack
    ): Response
    {
        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);
        if (!$currentPage) {
            $currentPage = 1;
        }
        $nextPage = $currentPage + 1;
        $previousPage = $currentPage -1;
        
        // charge categorie
        $blogPostCategory = $blogPostCategoryRepository->findOneBy(
            [
                'slug' => (string) $slug
            ]
        );
        if (!$blogPostCategory instanceof BlogPostCategory) {
            return $this->redirectToRoute('app_blog_blog');
        }

        // les articles à afficher
        $blogPosts = $blogPostRepository->findBy(
            [
                'status' => BlogPost::STATUS_PUBLISHED,
                'blogPostCategory' => $blogPostCategory
            ],
            [
                'datePublished' => 'DESC'
            ],
            self::NB_POST_BY_PAGE,
            ($currentPage - 1) * self::NB_POST_BY_PAGE
        );

        // le nombre d'article total
        $nbBlogPosts = $blogPostRepository->countCustom(
            [
                'status' => BlogPost::STATUS_PUBLISHED,
                'blogPostCategory' => $blogPostCategory
            ]
        );

        // le nombre de page
        $nbPages = ceil($nbBlogPosts / self::NB_POST_BY_PAGE);
        
        // fil arianne
        $this->breadcrumb->add(
            'Blog',
            $this->generateUrl('app_blog_blog')
        );
        $this->breadcrumb->add(
            $blogPostCategory->getName(),
            null
        );

        // rendu template
        return $this->render('blog/blogpostcategory/category.html.twig', [
            'blogPostCategory' => $blogPostCategory,
            'blogPosts' => $blogPosts,
            'nbPages' => $nbPages,
            'currentPage' => $currentPage,
            'nextPage' => $nextPage,
            'previousPage' => $previousPage
        ]);
    }

    #[Route('/blog/{slug}/', name: 'app_blog_post_details', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function details(
        $slug,
        BlogPostRepository $blogPostRepository
    ): Response
    {
        // charge blogPost
        $blogPost = $blogPostRepository->findOneBy(
            [
                'slug' => $slug
            ]
            );
        if (!$blogPost instanceof BlogPost) {
            return $this->redirectToRoute('app_blog_blog');
        }

        // fil arianne
        $this->breadcrumb->add(
            'Blog',
            $this->generateUrl('app_blog_blog')
        );
        $this->breadcrumb->add(
            $blogPost->getName(),
            null
        );
        
        // rendu template
        return $this->render('blog/blogpost/details.html.twig', [
            'blogPost' => $blogPost
        ]);
    }
}
