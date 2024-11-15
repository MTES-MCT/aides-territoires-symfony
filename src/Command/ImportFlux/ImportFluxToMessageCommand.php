<?php

namespace App\Command\ImportFlux;

use App\Message\Aid\MsgImportFlux;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'at:import_flux:to_message', description: 'Envoi les imports de flux au message bus')]
class ImportFluxToMessageCommand extends Command
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Lance les différentes commandes import_flux.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commands = [
            'at:import_flux:welcome_europe',
            'at:import_flux:ile_de_france',
            'at:import_flux:cdm',
            'at:import_flux:cddr',
            'at:import_flux:ministere_culture',
            'at:import_flux:region_sud',
            'at:import_flux:ademe_agir',
            'at:import_flux:nouvelle_aquitaine',
            'at:import_flux:pays_loire',
        ];

        foreach ($commands as $command) {
            $this->messageBus->dispatch(new MsgImportFlux($command));
        }

        return Command::SUCCESS;
    }
}
