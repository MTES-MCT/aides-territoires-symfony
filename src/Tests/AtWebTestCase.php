<?php

namespace App\Tests;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

abstract class AtWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected RouterInterface $router;
    protected ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->managerRegistry = static::getContainer()->get('doctrine');
    }
}
