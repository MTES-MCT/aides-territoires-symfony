<?php

namespace App\Controller\Contact;

use App\Controller\FrontController;
use App\Entity\Contact\ContactMessage;
use App\Entity\Log\LogContactFormSend;
use App\Form\Contact\ContactType;
use App\Service\Email\EmailService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends FrontController
{
    #[Route('/contact/', name: 'app_contact_contact')]
    public function index(
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        ParamService $paramService,
        EmailService $emailService
    ): Response {

        // fil arianne
        $this->breadcrumb->add(
            'Contact',
            null
        );


        $contact = new ContactMessage();

        $formContact = $this->createForm(ContactType::class, $contact);

        // vérification supplémentaire
        // des bots retire le required sur le sujet ce qui provoque une erreur dans le handleRequest
        if ($requestStack->getCurrentRequest()->request->has('contact')) {
            $contactRequest = $requestStack->getCurrentRequest()->get('contact');
            $subject = $contactRequest['subject'] ?? null;
            if (!$subject || trim($subject) == '') {
                $this->addFlash(FrontController::FLASH_ERROR, 'Une erreur est survenue lors de l\'envoi du message.');
                return $this->redirectToRoute('app_contact_contact', ['success' => 0]);
            }
        }

        $formContact->handleRequest($requestStack->getCurrentRequest());

        if ($formContact->isSubmitted()) {
            if ($formContact->isValid()) {
                // décode les apostrophe et saut de lignes bloqué par le santize
                $contact->setMessage(html_entity_decode($contact->getMessage()));
                // log
                $logContactFormSend = new LogContactFormSend();
                $logContactFormSend->setSubject($formContact->get('subject')->getData());
                $managerRegistry->getManager()->persist($logContactFormSend);

                // enregistre contact
                $managerRegistry->getManager()->persist($contact);
                $managerRegistry->getManager()->flush();

                // email admin
                $email_subject = "[aides-territoires] [Contact]";

                // envoi email
                $emailService->sendEmail(
                    $paramService->get('email_to'),
                    $email_subject,
                    'emails/contact/contact.html.twig',
                    [
                        'subject' => $email_subject,
                        'contact' => $contact,
                    ]
                );

                $this->addFlash(FrontController::FLASH_SUCCESS, 'Votre message a bien été envoyé.');

                return $this->redirectToRoute('app_contact_contact', ['success' => 1]);
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Une erreur est survenue lors de l\'envoi du message.');
            }
        }

        return $this->render('contact/contact/index.html.twig', [
            'controller_name' => 'ContactController',
            'formContact' => $formContact->createView(),
            'success' => $requestStack->getCurrentRequest()->get('success', null),
        ]);
    }
}
