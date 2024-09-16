<?php

namespace App\Tests\Controller\User;

use App\Entity\User\User;
use App\Tests\AtWebTestCase;

// php bin/phpunit src/Tests/Controller/User/RegisterControllerTest.php
class RegisterControllerTest extends AtWebTestCase
{
    public function testUserRegistration(): void
    {
        $route = $this->router->generate('app_user_user_register');
        $routeSuccess = $this->router->generate('app_user_dashboard');
        $crawler = $this->client->request('GET', $route);

        $form = $crawler->selectButton('Je crée mon compte')->form([
            'register[firstname]' => 'John',
            'register[lastname]' => 'Doe',
            'register[email]' => 'test@at.com',
            'register[password][first]' => '#123Password',
            'register[password][second]' => '#123Password',
            'register[perimeter]' => 1,
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects($routeSuccess);
    }
}