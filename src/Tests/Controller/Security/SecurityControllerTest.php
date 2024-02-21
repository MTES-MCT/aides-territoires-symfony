<?php

namespace App\Tests\Controller\Security;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * php bin/phpunit src/Tests/Controller/Security/SecurityControllerTest.php
 */
class SecurityControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
    }


    /**
     * @dataProvider provideAdmins
     */
    public function testLoginAdmins(string $email, string $redirectUrl): void
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $route = $router->generate('app_login_admin');

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
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $route = $router->generate('app_login');

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
}
