<?php

namespace App\Command\Import;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\ArrayInput;

#[AsCommand(name: 'at:import:global', description: 'Import generic')]
class GlobalImportCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Import global';
    protected string $commandTextEnd = '>Import global';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry
    )
    {
        ini_set('max_execution_time', 60*60*60);
        ini_set('memory_limit', '20G');
        parent::__construct();
    }

    protected function configure() : void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        $commands = [
            // phase 1/4
            // 'at:import:perimeter',
            // 'at:import:backer',
            // 'at:import:program',
            // 'at:import:financial_data',
            // 'at:import:organization',
            // 'at:import:user',
            // 'at:import:user_notification',
            // 'at:import:organization_beneficiairies',
            // 'at:import:perimeter_import',
            // 'at:import:eligibility',
            // 'at:import:category',
            // 'at:import:keyword',
            // 'at:import:data_source',
            // 'at:import:aid',
            // 'at:import:aid_associate_organization',
            // phase 2/4
            // 'at:import:project',
            // 'at:import:aid_project',
            // 'at:import:organization_favorite_projects',
            // 'at:import:organization_invitation',
            // 'at:import:bundle',
            // 'at:import:data_export',
            // 'at:import:blog',
            // 'at:import:blog_promotion',
            // 'at:import:search_page',
            // 'at:import:upload_image',
            // 'at:import:page',
            // 'at:import:alert',
            // phase 3/4
            // 'at:import:log_event',
            // 'at:import:log_arfnpwce_click',
            // 'at:import:log_admin_action',
            // 'at:import:log_aid_application_url_click',
            // 'at:import:log_aid_origin_url_click',
            // 'at:import:log_aid_contact_click',
            // phase 4/4
            'at:import:log_aid_view',
        ];

        $timeStart = microtime(true);

        foreach ($commands as $command) {
            try {
                $timeStartOperation = microtime(true);
                $command = $this->getApplication()->find($command);

                $arguments = [];

                $greetInput = new ArrayInput($arguments);
                $command->run($greetInput, $output);

                $timeEnd = microtime(true);
                $timeOperation = $timeEnd - $timeStartOperation;
                $timeGlobal = $timeEnd - $timeStart;

                $io->success('Fin '.$command->getName().' : '.gmdate("H:i:s", $timeEnd).' | ope : '.gmdate("H:i:s", $timeOperation).' | global : '.gmdate("H:i:s", $timeGlobal));

                // nettoyage memoire
                gc_collect_cycles();
                $this->managerRegistry->getManager()->clear();
            } catch (\Exception $exception) {
                $io->error($exception->getMessage());
                exit;
            }
        }

        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $io->success('Fin des opérations : '.gmdate("H:i:s", $timeEnd).' ('.gmdate("H:i:s", $time).')');

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

}