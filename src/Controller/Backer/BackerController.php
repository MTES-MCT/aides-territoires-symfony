<?php

namespace App\Controller\Backer;

use App\Controller\FrontController;
use App\Entity\Backer\Backer;
use App\Repository\Aid\AidRepository;
use App\Repository\Backer\BackerRepository;
use App\Service\Log\LogService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackerController extends FrontController
{
    #[Route('/partenaires/', name: 'app_backer_backer')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_cartography_cartography');
        return $this->render('backer/backer/index.html.twig', [

        ]);
    }

    #[Route('/partenaires/{id}-{slug}/', name: 'app_backer_details', requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+'])]
    public function details(
        $id,
        $slug,
        BackerRepository $backerRepository,
        AidRepository $aidRepository,
        LogService $logService,
        UserService $userService,
        RequestStack $requestStack
    ): Response
    {

        // charge backer
        $backer = $backerRepository->findOneBy(
            [
                'id' => $id,
                'slug' => $slug
            ]
            );
        if (!$backer instanceof Backer) {
            return $this->redirectToRoute('app_home');
        }

        $aidsParams = [
            'showInSearch' => true,
            'backer' => $backer,
        ];

        // défini les aides lives, à partir de quoi on pourra récupérer les financières, techniques, les thématiques
        $backer->setAidsLive($aidRepository->findCustom($aidsParams));

        // log
        $logService->log(
            type: LogService::BACKER_VIEW,
            params: [
                'host' => $requestStack->getCurrentRequest()->getHost(),
                'backer' => $backer,
                'organization' => $userService->getUserLogged() ? $userService->getUserLogged()->getDefaultOrganization() : null,
                'user' => $userService->getUserLogged(),
            ]
        );

        //foreach $backer->getAidsLive()
        $categories_by_theme=[];$programs_list=[];
        foreach($backer->getAidsLive() as $aid) {


            foreach($aid->getCategories() as $category){

                if(!isset($categories_by_theme[$category->getCategoryTheme()->getId()])){
                    $categories_by_theme[$category->getCategoryTheme()->getId()] = [
                        'categoryTheme' => $category->getCategoryTheme(),
                        'categories' => new ArrayCollection()
                    ];
                }
                if(!$categories_by_theme[$category->getCategoryTheme()->getId()]['categories']->contains($category)) {
                    $categories_by_theme[$category->getCategoryTheme()->getId()]['categories']->add($category);
                }
            }
            
            foreach($aid->getPrograms() as $program){

                if(!isset($programs_list[$program->getId()])){
                    $programs_list[$program->getId()] = [
                        'program' => $program,
                    ];
                }
            }
        }


        // fil arianne
        $this->breadcrumb->add(
            $backer->getName(),
            null
        );
        
        // rendu template
        return $this->render('backer/backer/details.html.twig', [
            'backer' => $backer,
            'forceDisplayAidAsList' => true,
            'aids' => $backer->getAidsLive(),
            'categories_by_theme' => $categories_by_theme,
            'programs_list' => $programs_list
        ]);
    }
}
