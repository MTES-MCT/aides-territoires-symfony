<?php

namespace App\Controller;

use App\Service\Various\Breadcrumb;
use App\Validator\UrlExternalValid;
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
}
