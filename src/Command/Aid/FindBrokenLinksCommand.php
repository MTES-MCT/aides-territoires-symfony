<?php

namespace App\Command\Aid;

use App\Entity\Aid\Aid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User\Notification;
use App\Entity\User\User;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use App\Service\Various\ParamService;

#[AsCommand(name: 'at:cron:aid:find_broken_links', description: 'Recherche si des aides publiées ont des liens casssés')]
class FindBrokenLinksCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Cron';
    protected string $commandTextEnd = '>Cron';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry,
        protected AidService $aidService,
        protected AidSearchFormService $aidSearchFormService,
        protected EmailService $emailService,
        protected ParamService $paramService
    )
    {
        ini_set('max_execution_time', 60*60*60);
        ini_set('memory_limit', '1.5G');
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

        try  {
            // generate menu
            $this->cronTask($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function cronTask($input, $output)
    {
        $io = new SymfonyStyle($input, $output);

        // charge les aides publiées sans lien cassé de noté
        $aids = $this->managerRegistry->getRepository(Aid::class)->findPublishedWithNoBrokenLink();

        $nbBrokenLinks = 0;
        $aidsWithBrokenLinks = [];

        /** @var Aid $aid */
        foreach ($aids as $key => $aid) {
            $aidsWithBrokenLinks[$key] = [
                'name' => $aid->getName(),
                'url' => $aid->getUrl(),
                'originUrl' => $aid->getOriginUrl(),
                'originUrlBroken' => false,
                'applicationUrl' => $aid->getApplicationUrl(),
                'applicationUrlBroken' => false
                
            ];

            // vérifie originUrl
            if ($aid->getOriginUrl()) {
                if ($this->checkUrl($aid->getOriginUrl())) {
                    $aid->setHasBrokenLink(false);
                } else {
                    $aid->setHasBrokenLink(true);
                    $aidsWithBrokenLinks['originUrlBroken'] = true;
                    $nbBrokenLinks++;
                }
            }

            // vérifie applicationUrl
            if ($aid->getApplicationUrl()) {
                if ($this->checkUrl($aid->getApplicationUrl())) {
                    $aid->setHasBrokenLink(false);
                } else {
                    $aid->setHasBrokenLink(true);
                    $aidsWithBrokenLinks['applicationUrlBroken'] = true;
                    $nbBrokenLinks++;
                }
            }

            // l'aide n'as pas de lien cassé, on la retire du tableau
            if (!$aid->isHasBrokenLink()) {
                unset($aidsWithBrokenLinks[$key]);
            }
        }

        $this->emailService->sendEmail(
            $this->paramService->get('email_super_admin'),
            $nbBrokenLinks. ' liens sont cassés dans des fiches aides',
            'emails/user/find_broken_links.html.twig',
            [
                'aidsWithBrokenLinks' => $aidsWithBrokenLinks,
            ]
        );
        
        // success
        $io->success('Nombre d\'aides avec lien cassé : ' . $nbBrokenLinks);
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }

    private function checkUrl($url): bool
    {
        $headers = @get_headers($url);
        if ($headers && strpos($headers[0], '200')) {
            return true;
        } else {
            return false;
        }
    }
}