<?php

namespace App\Service\Email;

use App\Entity\User\User;
use App\Service\Various\ParamService;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\CreateDoiContact;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailSender;
use Brevo\Client\Model\SendSmtpEmailTo;
use Brevo\Client\Model\UpdateContact;
use GuzzleHttp\Client;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class EmailService
{
    public function __construct(
        protected MailerInterface $mailerInterface,
        protected ParamService $paramService,
        protected RouterInterface $routerInterface
    ) {
    }

    /**
     * Envoi un email
     *
     * @param string $email
     * @param string $subject
     * @param string $template
     * @param array<string, mixed>|null $datas
     * @param array<string, mixed>|null $options
     * @return boolean
     */
    public function sendEmail(
        string $email,
        string $subject,
        string $template = 'emails/base.html.twig',
        ?array $datas = null,
        ?array $options = null
    ): bool {
        $form = $options['forceFrom'] ?? $this->paramService->get('email_from');
        $datas['subject'] = $datas['subject'] ?? $subject;

        try {
            $email = (new TemplatedEmail())
                ->from($form)
                ->to($email)
                ->subject($subject)
                ->htmlTemplate($template)
                ->context($datas);

            if ($options['attachments'] ?? false) {
                foreach ($options['attachments'] as $attachment) {
                    $email->addPart(new DataPart(new File($attachment)));
                }
            }

            $this->mailerInterface->send($email);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Undocumented function
     *
     * @param string $emailTo
     * @param string|null $nameTo
     * @param integer|null $templateId
     * @param array<string, mixed>|null $params
     * @param array<string, mixed>|null $headers
     * @param array<string, mixed>|null $tags
     * @return boolean
     */
    public function sendEmailViaApi(
        string $emailTo,
        ?string $nameTo = null,
        ?int $templateId = 0,
        ?array $params = null,
        ?array $headers = null,
        ?array $tags = null
    ): bool {
        // Configure API key authorization: api-key
        $config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            $this->paramService->get('sib_api_key')
        );

        $apiInstance = new TransactionalEmailsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new Client(),
            $config
        );
        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmailSender = new SendSmtpEmailSender([
            'name' => $this->paramService->get('email_from_name'),
            'email' => $this->paramService->get('email_from')
        ]);
        $sendSmtpEmail->setSender($sendSmtpEmailSender);

        // gestion du destinataire
        $to = [
            'email' => $emailTo
        ];
        if ($nameTo) {
            $to['name'] = $nameTo;
        }
        $sendSmtpEmail->setTo([new SendSmtpEmailTo($to)]);

        // si template
        if ($templateId) {
            $sendSmtpEmail->setTemplateId($templateId);
        }

        // si parametres
        if (is_array($params) && !empty($params)) {
            $sendSmtpEmail->setParams((object) $params);
        }

        if (is_array($headers) && !empty($headers)) {
            $sendSmtpEmail->setHeaders((object) $headers);
        }

        if (is_array($tags) && !empty($tags)) {
            $sendSmtpEmail->setTags($tags);
        }

        try {
            $apiInstance->sendTransacEmail($sendSmtpEmail);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Désinscrit un utilisateur sur le service d'email
     *
     * @param User $user
     * @return boolean
     */
    public function unsubscribeUser(
        User $user
    ): bool {
        // Configure API key authorization: api-key
        $config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            $this->paramService->get('sib_api_key')
        );


        $apiInstance = new ContactsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new Client(),
            $config
        );

        $updateContact = new UpdateContact();
        $newsletterListIds = explode(',', $this->paramService->get('sib_newsletter_list_ids'));
        foreach ($newsletterListIds as $key => $value) {
            $newsletterListIds[$key] = (int) $value;
        }
        $updateContact['unlinkListIds'] = $newsletterListIds;


        try {
            $apiInstance->updateContact($user->getEmail(), $updateContact);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function subscribeUser(User $user): bool
    {
        // Configure API key authorization: api-key
        $config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            $this->paramService->get('sib_api_key')
        );


        $apiInstance = new ContactsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new Client(),
            $config
        );

        $newsletterListIds = explode(',', $this->paramService->get('sib_newsletter_list_ids'));
        foreach ($newsletterListIds as $key => $value) {
            $newsletterListIds[$key] = (int) $value;
        }

        $doubleOptin = new CreateDoiContact();
        $doubleOptin['attributes'] = [
            'DOUBLE_OPT_IN' => 1
        ];
        $doubleOptin['includeListIds'] = $newsletterListIds;
        $doubleOptin['email'] = $user->getEmail();
        $doubleOptin['templateId'] = (int) $this->paramService->get('sib_newsletter_confirm_template_id');
        $doubleOptin['redirectionUrl'] = $this->routerInterface->generate(
            'app_newsletter_register_success',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            $apiInstance->createDoiContact($doubleOptin);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function updateUser(User $user, array $datas): bool
    {
        // Configure API key authorization: api-key
        $config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            $this->paramService->get('sib_api_key')
        );


        $apiInstance = new ContactsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new Client(),
            $config
        );

        $newsletterListIds = explode(',', $this->paramService->get('sib_newsletter_list_ids'));
        foreach ($newsletterListIds as $key => $value) {
            $newsletterListIds[$key] = (int) $value;
        }

        $identifier = $user->getEmail();
        $updateContact = new UpdateContact();
        if (isset($datas['attributes']) && is_array($datas['attributes']) && count($datas['attributes']) > 0) {
            $updateContact['attributes'] = $datas['attributes'];
        }
        if (isset($datas['listIds']) && is_array($datas['listIds']) && count($datas['listIds']) > 0) {
            $updateContact['listIds'] = $datas['listIds'];
        }

        try {
            $apiInstance->updateContact($identifier, $updateContact);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isUserMlConsent($user): bool
    {
        // Configure API key authorization: api-key
        $config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            $this->paramService->get('sib_api_key')
        );

        $apiInstance = new ContactsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new Client(),
            $config
        );

        try {
            $result = $apiInstance->getContactInfo($user->getEmail());
            foreach ($result['listIds'] as $listId) {
                if (in_array($listId, explode(',', $this->paramService->get('sib_newsletter_list_ids')))) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
