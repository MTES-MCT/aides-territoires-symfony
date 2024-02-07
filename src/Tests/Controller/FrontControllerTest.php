<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
/**
 * php bin/phpunit src/Tests/Controller/FrontControllerTest.php
 * On test que les pages du site n'ont pas de code 500 ou plus (codes d'erreurs)
 */
class FrontControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideRoutes
     */
    public function testPageSuccessfullyRespondWithoutError500(string $path, int $statusCode): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', $path);

        $this->assertLessThan(
            $statusCode,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    public function provideRoutes(): \Generator
    {
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $routes = $router->getRouteCollection();

        /** @var Route $route */
        foreach ($routes as $route) {
            // on ne test pas l'api ici
            if (strpos($route->getPath(), 'api') !== false) {
                continue;
            }
            if ([] === $route->getMethods() ||
                (1 === \count($route->getMethods())) && \in_array('GET', $route->getMethods())
            ) {
                $path = $route->getPath();
                yield $path => [$path, 500];
            }
        }
    }
}