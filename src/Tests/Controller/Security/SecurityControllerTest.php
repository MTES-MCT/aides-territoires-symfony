<?php

namespace App\Tests\Controller\Security;

use App\Entity\User\User;
use App\Tests\AtWebTestCase;

/**
 * php bin/phpunit src/Tests/Controller/Security/SecurityControllerTest.php
 */
class SecurityControllerTest extends AtWebTestCase
{
    /**
     * @dataProvider provideAdmins
     */
    public function testLoginAdmins(string $email, string $redirectUrl): void
    {
        $route = $this->router->generate('app_login_admin');

        $crawler = $this->client->request('GET', $route);

        $form = $crawler->selectButton('Connectez-vous')->form();
        $form['_username'] = $email;
        $form['_password'] = '#123Password';

        $this->client->submit($form);
        $this->assertResponseRedirects($redirectUrl);
    }

    public function provideAdmins(): \Generator
    {
        yield 'Admin can login as Admin' => ['admin@aides-territoires.beta.gouv.fr', '/'];
    }


    /**
     * @dataProvider provideUsers
     */
    public function testLogin(string $email, string $redirectUrl): void
    {
        $route = $this->router->generate('app_login');

        $crawler = $this->client->request('GET', $route);

        $form = $crawler->selectButton('Connectez-vous')->form();
        $form['_username'] = $email;
        $form['_password'] = '#123Password';

        $this->client->submit($form);
        $this->assertResponseRedirects($redirectUrl);
    }

    public function provideUsers(): \Generator
    {
        yield 'User login' => ['user@aides-territoires.beta.gouv.fr', '/comptes/moncompte/'];
    }

    public function testApiLoginWithoutAuthToken(): void
    {
        $route = $this->router->generate('app_login_api');

        $this->client->request('POST', $route);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testApiLogin(): void
    {
        // Récupérer l'utilisateur des fixtures
        $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => 'user@aides-territoires.beta.gouv.fr']);

        // Vérifier que l'utilisateur existe et a un apiToken
        $this->assertNotNull($user);
        $this->assertNotEmpty($user->getApiToken());

        // Récupérer le routeur pour générer l'URL de l'API de connexion
        $route = $this->router->generate('app_login_api');

        // Envoyer la requête GET à l'API de connexion avec le token dans les en-têtes
        $this->client->request('POST', $route, [], [], [
            'HTTP_X-AUTH-TOKEN' => $user->getApiToken(),
        ]);

        // Vérifier que la réponse est de type JSON
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        // Vérifier que la réponse contient un token
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertNotEmpty($responseData['token']);
    }
}
