<?php

namespace App\Tests\Controller\Contact;

use App\Tests\AtWebTestCase;

// php bin/phpunit src/Tests/Controller/Contact/ContactControllerTest.php
class ContactControllerTest extends AtWebTestCase
{
    public function testSubmitValidContactForm()
    {
        $route = $this->router->generate('app_contact_contact');
        $routeSuccess = $this->router->generate('app_contact_contact', ['success' => 1]);
        $crawler = $this->client->request('GET', $route);

        $form = $crawler->selectButton('Envoyer')->form([
            'contact[firstname]' => 'Test',
            'contact[lastname]' => 'User',
            'contact[email]' => 'test@at.com',
            'contact[phoneNumber]' => '0123456789',
            'contact[structureAndFunction]' => 'Test Structure',
            'contact[subject]' => 'contact_tech',
            'contact[message]' => 'Test Message',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects($routeSuccess);
    }

    public function testSubmitInvalidContactForm()
    {
        $route = $this->router->generate('app_contact_contact');
        $routeError = $this->router->generate('app_contact_contact', ['success' => 0]);
        $crawler = $this->client->request('GET', $route);

        $form = $crawler->selectButton('Envoyer')->form([
            'contact[firstname]' => '',
            'contact[lastname]' => '',
            'contact[email]' => 'invalid-email',
            'contact[phoneNumber]' => '',
            'contact[structureAndFunction]' => '',
            'contact[subject]' => '',
            'contact[message]' => '',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects($routeError);
    }
}
