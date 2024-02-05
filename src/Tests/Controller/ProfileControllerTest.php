<?php

namespace App\Tests\Controller;

use App\Repository\Organization\OrganizationRepository;
use App\Repository\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * php bin/phpunit src/Tests/Controller/ProfileControllerTest.php
 */
class ProfileControllerTest extends WebTestCase
{
    // ...

    public function testVisitingWhileLoggedIn(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByEmail('remi.barret@beta.gouv.fr');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        // test e.g. the profile page
        $client->request('GET', '/');
        // $client->request('GET', '/comptes/moncompte/');
        $this->assertResponseIsSuccessful();
        // $this->assertSelectorTextContains('h1', 'Bienvenue sur votre compte Rémi !');
    }

    public function testCreateAide(): void
    {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UserRepository::class);
    $organizationRepository = static::getContainer()->get(OrganizationRepository::class);
    // retrieve the test user
    $testUser = $userRepository->findOneByEmail('remi.barret@beta.gouv.fr');

    // simulate $testUser being logged in
    $client->loginUser($testUser);

    // Define the data for the new aide
    $aideData = [
        'name' => 'Test Aide',
        'organization' => $organizationRepository->findOneBy(['name' => 'organizationTest']),
        // Add other fields as necessary
    ];

    // Send a POST request to the endpoint that creates an aide
    $client->request('POST', '/comptes/aides/publier/', $aideData);

    // Check that the response status code is 302 (redirect)
    $this->assertEquals(302, $client->getResponse()->getStatusCode());

    // Follow the redirect and check that the aide has been created
    $crawler = $client->followRedirect();

    // Check that the title of the new aide is present on the page
    $this->assertSelectorTextContains('h1', 'Test Aide');
}
}