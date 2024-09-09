<?php

namespace App\Controller;

use App\Entity\Log\LogAidSearchTemp;
use App\Service\Various\Breadcrumb;
use App\Validator\UrlExternalValid;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(priority: 1)]
class FrontController extends AbstractController
{
    const FLASH_SUCCESS = 'success';
    const FLASH_ERROR = 'error';

    public function __construct(
        public Breadcrumb $breadcrumb,
        public TranslatorInterface $translatorInterface
    ) {
    }

    /**
     * Pour les addFlash traduits
     * @param $type
     * @param $message
     */
    public function tAddFlash($type, $message)
    {
        $this->addFlash(
            $type,
            $this->translatorInterface->trans($message)
        );
    }

    #[Route('/test', name: 'app_test')]
    public function test(
        ManagerRegistry $managerRegistry
    )
    {
        $logAidSearchTemps = $managerRegistry->getRepository(LogAidSearchTemp::class)->findAll();
        foreach ($logAidSearchTemps as $logAidSearchTemp) {
            for ($j=0; $j<20; $j++) {
                for ($i=0; $i<1000; $i++) {
                    // on duplique $logAidSearchTemp 1000 fois en clonant l'entite
                    $logAidSearch = clone $logAidSearchTemp;
                    $managerRegistry->getManager()->persist($logAidSearch);

                }
                $managerRegistry->getManager()->flush();
            }
        }
    }
}
