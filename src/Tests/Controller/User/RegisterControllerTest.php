<?php

namespace App\Tests\Controller\User;

use App\Tests\AtWebTestCase;

// php bin/phpunit src/Tests/Controller/User/RegisterControllerTest.php
class RegisterControllerTest extends AtWebTestCase
{
    public function testUserRegistration(): void
    {
        $route = $this->router->generate('app_user_user_register');
        $routeSuccess = $this->router->generate('app_user_dashboard');
        $crawler = $this->client->request('GET', $route);

        // Ajouter dynamiquement une option au champ <select>
        $select = $crawler->filter('select[name="register[perimeter]"]')->first();
        $domSelect = $select->getNode(0);
        $option = $domSelect->ownerDocument->createElement('option', 'perimeterTest (Ad-hoc)');
        $option->setAttribute('value', '1');
        $domSelect->appendChild($option);

        $form = $crawler->selectButton('Je crée mon compte')->form([
            'register[firstname]' => 'John',
            'register[lastname]' => 'Doe',
            'register[email]' => 'test@at.com',
            'register[password][first]' => '#123Password',
            'register[password][second]' => '#123Password',
            'register[perimeter]' => 1,
        ]);

        $this->client->submit($form);

        // Vérifier l'URL de redirection
        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        $this->assertEquals($routeSuccess, $redirectUrl, "L'URL de redirection est incorrecte : $redirectUrl");
        
    }
}