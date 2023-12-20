<?php

namespace App\Command\Import\Aid;

use App\Command\Import\ImportCommand;
use App\Entity\Aid\Aid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:import:aid_associate_organization', description: 'Association aid / organization')]
class AidAssociateOrganizationCommand extends ImportCommand
{
    protected string $commandTextStart = '>Association aid / organization';
    protected string $commandTextEnd = '<Association aid / organization';

    protected function import($input, $output)
    {
        // ==================================================================
        // AID ASSOCIATE ORGANIZATION
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('AID ASSOCIATE ORGANIZATION');

        $aids = $this->managerRegistry->getRepository(Aid::class)->findAll();

        // progressbar
        $io->createProgressBar(count($aids));

        // starts and displays the progress bar
        $io->progressStart();

        /** @var Aid $aid */
        foreach ($aids as $aid) {
            if ($aid->getAuthor() && $aid->getAuthor()->getDefaultOrganization()) {
                $aid->setOrganization($aid->getAuthor()->getDefaultOrganization());
                $this->managerRegistry->getManager()->persist($aid);
            }

            $io->progressAdvance();
        }

        $this->managerRegistry->getManager()->flush();

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}