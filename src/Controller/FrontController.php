<?php

namespace App\Controller;

use App\Service\Various\Breadcrumb;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
