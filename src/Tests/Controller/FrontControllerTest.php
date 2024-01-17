<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * php bin/phpunit src/Tests/Controller/FrontControllerTest.php
 */
class FrontControllerTest extends WebTestCase
{
    public function testUrls()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'localhost:8080']);

        $urls = $this->getUrls();

        foreach ($urls as $url) {
            $crawler = $client->request('GET', $url);
            $this->assertResponseIsSuccessful(sprintf('The %s URL loads correctly.', $url));
        }
    }

    private function getUrls()
    {
        return [
            '/',
            '/programmes/',
            // ajoutez d'autres URLs ici...
        ];
    }
}