<?php

namespace App\Controller;

use App\Repository\Aid\AidRepository;
use App\Service\Various\Breadcrumb;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority: 1)]
class FrontController extends AbstractController
{
    public const FLASH_SUCCESS = 'success';
    public const FLASH_ERROR = 'error';

    public function __construct(
        public Breadcrumb $breadcrumb,
        public TranslatorInterface $translatorInterface
    ) {
    }

    /**
     * Pour les addFlash traduits
     * @param string $type
     * @param string $message
     */
    public function tAddFlash(string $type, string $message): void
    {
        $this->addFlash(
            $type,
            $this->translatorInterface->trans($message)
        );
    }

    #[Route('/test_request/', name: 'app_test_request')]
    public function testRequests(
        AidRepository $aidRepository,
        ManagerRegistry $managerRegistry
    ): Response
    {
        $response = '';

        $timeStart = microtime(true);
        $countAids = $aidRepository->countCustom();
        unset($countAids);
        $managerRegistry->getManager()->clear();
        $timeEnd = microtime(true);
        $executionTime = $timeEnd - $timeStart;
        $response .= '<p>'.(round($executionTime * 1000)).'</p>';

        $timeStart = microtime(true);
        $aids = $aidRepository->findCustom([
            'showInSearch',
            'maxResults' => 1000
        ]);
        unset($aids);
        $managerRegistry->getManager()->clear();
        $timeEnd = microtime(true);
        $executionTime = $timeEnd - $timeStart;
        $response .= '<p>'.(round($executionTime * 1000)).'</p>';

        $timeStart = microtime(true);
        $qb = $aidRepository->getQueryBuilderForSearch([
            'showInSearch' => true
        ]);
        $aids = $qb->getQuery()->getResult();
        unset($aids);
        $managerRegistry->getManager()->clear();
        $timeEnd = microtime(true);
        $executionTime = $timeEnd - $timeStart;
        $response .= '<p>'.(round($executionTime * 1000)).'</p>';

        
        return new Response($response);
    }
}
