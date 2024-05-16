<?php

namespace App\Controller;

use App\Entity\Perimeter\Perimeter;
use App\Entity\Reference\KeywordReference;
use App\Form\Aid\AidSearchTypeV2;
use App\Form\Program\CountySelectType;
use App\Form\Reference\ProjectReferenceSearchType;
use App\Message\SendNotification;
use App\Repository\Aid\AidRepository;
use App\Repository\Backer\BackerRepository;
use App\Repository\Blog\BlogPostRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Repository\Program\ProgramRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\Reference\KeywordReferenceRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use TextAnalysis\Tokenizers\GeneralTokenizer;

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
        MessageBusInterface $bus,
        ManagerRegistry $managerRegistry
    ): Response
    {
        $aid = $aidRepository->find(145007);

        $description = $aid->getDescription();
        
        $commonWords = [
            'pour', 'des', 'ces', 'que', 'qui', 'nous', 'vous', 'mais', 'avec', 'cette', 'dans', 'sur', 'fait', 'elle', 'tout', 'son', 'sont', 'aux', 'par', 'comme', 'peut', 'plus', 'sans', 'ses', 'donc', 'quand', 'depuis', 'leur', 'sous', 'tous', 'très', 'fait', 'était', 'aussi', 'cela', 'entre', 'avant', 'après', 'tous', 'autre', 'trop', 'encore', 'alors', 'ainsi', 'chez', 'leurs', 'dont', 'cette', 'faire', 'part', 'quel', 'elle', 'même', 'moins', 'peu', 'car', 'aucun', 'chaque', 'toute', 'fois', 'quelque', 'manière', 'chose', 'autres', 'beaucoup', 'toutes', 'ceux', 'celles', 'devant', 'depuis', 'derrière', 'dessous', 'dessus', 'contre', 'pendant', 'malgré', 'hors', 'parmi', 'sans', 'sauf', 'selon', 'sous', 'vers'
        ];
        
        
        // Retirer les caractères spéciaux sauf les caractères accentués
        $description = preg_replace('/[^a-z0-9\sàâäéèêëïîôöùûüÿç]/ui', '', $description);
        
        
        // Retirer les mots de moins de 3 lettres
        $description = preg_replace('/\b\w{1,2}\b/u', '', $description);
        
        
        // Retirer les mots communs
        $commonWordsPattern = '/\b(' . implode('|', $commonWords) . ')\b/ui';
        $description = preg_replace($commonWordsPattern, '', $description);

        /** @var KeywordReferenceRepository $keywordReferenceRepository */
        $keywordReferenceRepository = $this->managerRegistry->getRepository(KeywordReference::class);

        // Tokenize la description
        $tokens = tokenize(strip_tags($description));
        $keywords = [];
        $freqDist = freq_dist($tokens);
        foreach ($freqDist->getKeyValuesByFrequency() as $item => $freq) {
            if ($freq < 2) {
                continue;
            }
            $keyword = $keywordReferenceRepository->findOneBy(['name' => $item]);
            if ($keyword instanceof KeywordReference) {
                $keywords[] = $keyword;
            }
        }
        // dd($freqDist, $tokens);
        exit;
        // will cause the SmsNotificationHandler to be called
        for ($i=0; $i<1000; $i++) {
            $bus->dispatch(new SendNotification('Titre test', 'Look! I created a message!'));
        }
        $formAidSearch = $this->createForm(
            AidSearchTypeV2::class,
            $aidSearchFormService->getAidSearchClass(),
            [
                'action' => $this->generateUrl('app_aid_aid'),
                'method' => 'GET'
            ]
        );

        // formulaire de recherche projet
        $formProjectReferenceSearch = $this->createForm(
            ProjectReferenceSearchType::class,
            null,
            [
                'action' => $this->generateUrl('app_project_reference')
            ]
        );

        // Program mis en avant
        $program = $programRepository->findOneBy([
            'slug' => 'fonds-vert'
        ]);

        // les articles du blog
        $blogPosts = $blogPostRepository->getRecents();

        // formulaire choix département
        $formCounties = $this->createForm(CountySelectType::class);
        $formCounties->handleRequest($requestStack->getCurrentRequest());
        if ($formCounties->isSubmitted()) {
            if ($formCounties->isValid()) {
                $county = $perimeterRepository->findOneBy([
                    'code' => $formCounties->get('county')->getData()
                ]);
                if ($county instanceof Perimeter) {
                    return $this->redirectToRoute('app_cartography_detail', [
                        'code' => $county->getCode(),
                        'slug' => $stringService->getSlug($county->getName())
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
                'backers_count' => $county->getBackersCount()
            ];
        }
        
        
        // nb aides
        $nbAids = $aidRepository->countLives();

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
            'departmentsData' => $departmentsData
        ]);
    }
}
