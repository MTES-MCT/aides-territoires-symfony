<?php

namespace App\Controller;

use App\Entity\Perimeter\Perimeter;
use App\Form\Aid\AidSearchTypeV2;
use App\Form\Program\CountySelectType;
use App\Form\Reference\ProjectReferenceSearchType;
use App\Repository\Aid\AidRepository;
use App\Repository\Backer\BackerRepository;
use App\Repository\Blog\BlogPostRepository;
use App\Repository\Log\LogEventRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Program\ProgramRepository;
use App\Repository\Project\ProjectRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends FrontController
{
    #[Route('/', name: 'app_home')]
    public function index(
        RequestStack $requestStack,
        ProgramRepository $programRepository,
        BlogPostRepository $blogPostRepository,
        PerimeterRepository $perimeterRepository,
        BackerRepository $backerRepository,
        AidRepository $aidRepository,
        ProjectRepository $projectRepository,
        StringService $stringService,
        AidSearchFormService $aidSearchFormService,
        LogEventRepository $logEventRepository,
        ParamService $paramService,
    ): Response {
        $formAidSearch = $this->createForm(
            AidSearchTypeV2::class,
            $aidSearchFormService->getAidSearchClass(),
            [
                'action' => $this->generateUrl('app_aid_aid'),
                'method' => 'GET',
                'isPageHome' => true,
            ]
        );

        // formulaire de recherche projet
        $formProjectReferenceSearch = $this->createForm(
            ProjectReferenceSearchType::class,
            null,
            [
                'action' => $this->generateUrl('app_project_reference'),
            ]
        );

        // Program fond vert mis en avant, le nom change chaque année mais pas l'id
        $program = $programRepository->find($paramService->get('id_program_fond_vert'));

        // les articles du blog
        $blogPosts = $blogPostRepository->getRecents();

        // formulaire choix département
        $formCounties = $this->createForm(CountySelectType::class);
        $formCounties->handleRequest($requestStack->getCurrentRequest());
        if ($formCounties->isSubmitted()) {
            if ($formCounties->isValid()) {
                $county = $perimeterRepository->findOneBy([
                    'code' => $formCounties->get('county')->getData(),
                ]);
                if ($county instanceof Perimeter) {
                    return $this->redirectToRoute('app_cartography_detail', [
                        'code' => $county->getCode(),
                        'slug' => $stringService->getSlug($county->getName()),
                    ]);
                }
            }
        }

        $counties = $perimeterRepository->findCounties();
        $departmentsData = [];

        foreach ($counties as $county) {
            $departmentsData[] = [
                'code' => $county->getCode(),
                'name' => $county->getName(),
                'slug' => $stringService->getSlug($county->getName()),
                'backers_count' => $county->getBackersCount(),
            ];
        }

        // nb aides
        $nbAids = $logEventRepository->getLatestSiteCountAidLives();

        // nb porteur
        $nbBackers = $backerRepository->countWithAids();

        // les backers selecitonnés
        $backerLogos = $backerRepository->findSelectedForHome();

        // les programs selectionnés
        $programLogos = $programRepository->finddSelecteForHome();
        $nbPrograms = $programRepository->countCustom();

        // les aides récentes
        $recentAids = $aidRepository->findRecent();

        // derniers projets public
        $publicProjects = $projectRepository->findPublicProjects(['limit' => 3]);

        // rendu template
        return $this->render('home.html.twig', [
            'no_breadcrumb' => true,
            'formAidSearch' => $formAidSearch->createView(),
            'formProjectReferenceSearch' => $formProjectReferenceSearch->createView(),
            'noAdvanceFilters' => true,
            'noNewSearch' => true,
            'program' => $program,
            'blogPosts' => $blogPosts,
            'formCounties' => $formCounties->createView(),
            'nbAids' => $nbAids,
            'nbBackers' => $nbBackers,
            'backerLogos' => $backerLogos,
            'programLogos' => $programLogos,
            'nbPrograms' => $nbPrograms,
            'recentAids' => $recentAids,
            'publicProjects' => $publicProjects,
            'departmentsData' => $departmentsData,
        ]);
    }
}
