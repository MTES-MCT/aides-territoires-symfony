<?php

namespace App\Tests\Controller\Contact;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// php bin/phpunit src/Tests/Controller/Contact/ContactControllerTest.php
class ContactControllerTest extends WebTestCase
{
    public function testSubmitValidContactForm()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact/');

        $form = $crawler->selectButton('Envoyer')->form([
            'contact[firstname]' => 'Test',
            'contact[lastname]' => 'User',
            'contact[email]' => 'test@example.com',
            'contact[phoneNumber]' => '0123456789',
            'contact[structureAndFunction]' => 'Test Structure',
            'contact[subject]' => 'contact_tech',
            'contact[message]' => 'Test Message',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/contact/?success=1');
    }

    public function testSubmitInvalidContactForm()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact/');

        $form = $crawler->selectButton('Envoyer')->form([
            'contact[firstname]' => '',
            'contact[lastname]' => '',
            'contact[email]' => 'invalid-email',
            'contact[phoneNumber]' => '',
            'contact[structureAndFunction]' => '',
            'contact[subject]' => '',
            'contact[message]' => '',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/contact/?success=0');
    }
}