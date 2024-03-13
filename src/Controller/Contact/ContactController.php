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
    ): Response
    {

        // fil arianne
        $this->breadcrumb->add(
            'Contact',
            null
        );


        $contact = new ContactMessage();

        $formContact = $this->createForm(ContactType::class, $contact);

        // vérification supplémentaire
        // des bots retire le required sur le sujet ce qui provoque une erreur dans le handleRequest
        if ($requestStack->getCurrentRequest()->request->has('contact') ) {
            $contact = $requestStack->getCurrentRequest()->get('contact');
            $subject = $contact['subject'] ?? null;
            if (!$subject || trim($subject) == '') {
                $this->addFlash(FrontController::FLASH_ERROR, 'Une erreur est survenue lors de l\'envoi du message.');
                return $this->redirectToRoute('app_contact_contact', ['success'=>0]);
            }
        }
        
        $formContact->handleRequest($requestStack->getCurrentRequest());

        if ($formContact->isSubmitted()){ 
            if ($formContact->isValid()) {
                // log
                $logContactFormSend = new LogContactFormSend();
                $logContactFormSend->setSubject($formContact->get('subject')->getData());
                $managerRegistry->getManager()->persist($logContactFormSend);

                // enregistre contact
                $managerRegistry->getManager()->persist($contact);
                $managerRegistry->getManager()->flush();

                // email admin
                $email_subject = "[aides-territoires] [Contact]";

                $content_email="<p>Message reçu via le formulaire de contact.</p>";
                $content_email.="<p>De :</p><ul>";
                $content_email.="<li>Email : ".$formContact->get('email')->getData()."</li>";
                if($formContact->get('firstname')->getData()){
                    $content_email.="<li>Prénom : ".$formContact->get('firstname')->getData()."</li>";
                }
                if($formContact->get('lastname')->getData()){
                    $content_email.="<li>Nom : ".$formContact->get('lastname')->getData()."</li>";
                }
                if($formContact->get('phoneNumber')->getData()){
                    $content_email.="<li>Téléphone : ".$formContact->get('phoneNumber')->getData()."</li>";
                }
                if($formContact->get('structureAndFunction')->getData()){
                    $content_email.="<li>Structure et fonction : ".$formContact->get('structureAndFunction')->getData()."</li>";
                }
                $content_email.="</ul>";
                $content_email.="<p>Sujet : ".ContactType::SUBJECTS[$formContact->get('subject')->getData()]."</p>";
                $content_email.="<p>Message :<br />".$formContact->get('message')->getData()."</p>";

                // envoi email
                $emailService->sendEmail(
                    $paramService->get('email_to'),
                    $email_subject,
                    'emails/base.html.twig',
                    [
                        'subject' => $email_subject,
                        'body' => $content_email,
                    ]
                );

                $this->addFlash(FrontController::FLASH_SUCCESS, 'Votre message a bien été envoyé.');

                return $this->redirectToRoute('app_contact_contact', ['success'=>1]);
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Une erreur est survenue lors de l\'envoi du message.');
            }
        }


        return $this->render('contact/contact/index.html.twig', [
            'controller_name' => 'ContactController',
            'formContact' => $formContact->createView(),
            'success' => $requestStack->getCurrentRequest()->get('success', null)
        ]);
    }
}
