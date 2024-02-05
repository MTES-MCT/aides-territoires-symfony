<?php

namespace App\Tests\Controller;

use App\Entity\Aid\Aid;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use GuzzleHttp\Client;
/**
 * php bin/phpunit src/Tests/Controller/FrontControllerTest.php
 */
class FrontControllerTest extends WebTestCase
{
    public function testUrls()
    {
        $client = new Client();
        foreach ($this->getUrls() as $url) {
            try {
            $response = $client->get($this->getBaseUrl().$url);
            $this->assertEquals(200, $response->getStatusCode());
            } catch (\Exception $e) {
                $this->fail($url.' : '.$e->getCode());
            }
        }
    }

    private function getBaseUrl(): string{
        // en docker, ip locale
        // docker ps pour afficher les container
        // docker inspect at_apache pour avoir l'ip
        return 'http://172.27.0.4';
    }

    private function getUrls()
    {
        // // (1) boot the Symfony kernel
        // self::bootKernel();

        // // (2) use static::getContainer() to access the service container
        // $container = static::getContainer();
        
        // $managerRegistry =  $container->get('doctrine');
        // $manager = $managerRegistry->getManager();
        // $aids = $manager->getRepository(Aid::class)->findAll();
        // $urls = [];
        // foreach ($aids as $aid) {
        //     $urls[] = '/aides/'.$aid->getSlug();
        // }
        // return $urls;

        return [
            '/',
            '/programmes/',
            '/blog/',
            // ajoutez d'autres URLs ici...
        ];
    }
}