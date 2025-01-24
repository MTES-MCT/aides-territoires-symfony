<?php

namespace App\Command\Cron\Site;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'at:cron:site:search_cache_reset', description: 'Vide le cache recherche')]
class SearchCacheResetCommand extends Command
{
    public function __construct(
        private KernelInterface $kernelInterface
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // gestion du cache symfony
        $arguments = ['--env' => $this->kernelInterface->getEnvironment()];
        $commands = [
            'cache:clear',
            'cache:warmup',
        ];
        foreach ($commands as $command) {
            $command = $this->getApplication()->find($command);
            $greetInput = new ArrayInput($arguments);
            $command->run($greetInput, $output);
        }

        return Command::SUCCESS;
    }
}
