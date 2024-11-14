<?php

namespace App\Tests\Controller;

use App\Tests\AtWebTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * php bin/phpunit src/Tests/Controller/FrontControllerTest.php
 * On test que les pages du site n'ont pas de code 500 ou plus (codes d'erreurs).
 */
class FrontControllerTest extends AtWebTestCase
{
    /**
     * @dataProvider provideRoutes
     */
    public function testPageSuccessfullyRespondWithoutError500(string $path, int $statusCode): void
    {
        $this->client->request('GET', $path);

        $this->assertLessThan(
            $statusCode,
            $this->client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $this->client->getResponse()->getStatusCode())
        );
    }

    public function provideRoutes(): \Generator
    {
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $routes = $router->getRouteCollection();

        /** @var Route $route */
        foreach ($routes as $route) {
            // les urls à ne pas tester
            $notToTest = [
                '/comptes/connexion/proconnect/',
                '/comptes/proconnect/',
                '/logout',
            ];

            // on ne test pas l'api ici
            if (false !== strpos($route->getPath(), 'api')) {
                continue;
            }
            if (in_array($route->getPath(), $notToTest)) {
                continue;
            }
            if (
                [] === $route->getMethods()
                || (1 === \count($route->getMethods())) && \in_array('GET', $route->getMethods())
            ) {
                $path = $route->getPath();
                yield $path => [$path, 500];
            }
        }
    }
}
