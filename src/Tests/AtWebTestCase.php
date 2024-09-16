<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

abstract class AtWebTestCase extends WebTestCase
{
    protected $client;
    protected $router;
    protected $managerRegistry;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->managerRegistry = static::getContainer()->get('doctrine');
    }
}