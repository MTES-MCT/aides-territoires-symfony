<?php

namespace App\Command\Cron\Site;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'at:cron:site:cache_clear', description: 'Vide le cache Redis')]
class ClearCacheCommand extends Command
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cache->clear();
        $output->writeln('Cache vidé avec succès');

        return Command::SUCCESS;
    }
}
