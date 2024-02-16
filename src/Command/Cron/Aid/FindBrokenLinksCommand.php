<?php

namespace App\Command\Cron\Aid;

use App\Entity\Aid\Aid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Persistence\ManagerRegistry;
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
        ini_set('memory_limit', '1G');
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
        $timeStart = microtime(true);

        $today = new \DateTime(date('Y-m-d'));
        // charge les aides publiées sans lien cassé de noté
        $aids = $this->managerRegistry->getRepository(Aid::class)->findPublishedWithNoBrokenLink([
            'dateCheckBrokenLinkMax' => $today
        ]);

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
                    $aidsWithBrokenLinks[$key]['originUrlBroken'] = true;
                    $this->managerRegistry->getManager()->persist($aid);
                    $nbBrokenLinks++;
                }
            }

            // vérifie applicationUrl
            if ($aid->getApplicationUrl()) {
                if ($this->checkUrl($aid->getApplicationUrl())) {
                    $aid->setHasBrokenLink(false);
                } else {
                    $aid->setHasBrokenLink(true);
                    $aidsWithBrokenLinks[$key]['applicationUrlBroken'] = true;
                    $this->managerRegistry->getManager()->persist($aid);
                    $nbBrokenLinks++;
                }
            }

            // met à jour la date de vérification
            $aid->setDateCheckBrokenLink($today);
            $this->managerRegistry->getManager()->persist($aid);

            // l'aide n'as pas de lien cassé, on la retire du tableau
            if (!$aid->isHasBrokenLink()) {
                unset($aidsWithBrokenLinks[$key]);
            }
        }


        $this->managerRegistry->getManager()->flush();


        $this->emailService->sendEmail(
            $this->paramService->get('email_super_admin'),
            $nbBrokenLinks. ' liens sont cassés dans des fiches aides',
            'emails/aid/find_broken_links.html.twig',
            [
                'aidsWithBrokenLinks' => $aidsWithBrokenLinks,
            ]
        );
        
        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        // success
        $io->success('Temps écoulé : '.gmdate("H:i:s", $timeEnd).' ('.gmdate("H:i:s", intval($time)).')');
        $io->success('Nombre d\'aides avec lien cassé : ' . $nbBrokenLinks);
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }

    private function checkUrl($url): bool
    {
        // try {
        //     $headers = get_headers($url);
        //     if (!isset($headers[0])) {
        //         return false;
        //     }
        //     return strpos($headers[0], '200');
        // } catch (\Exception $e) {
        //     return false;
        // }
        $ch = curl_init($url);

        // Définir l'option pour retourner le transfert en tant que chaîne
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // Définir l'option pour ne récupérer que les en-têtes
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
    
        // Définir un délai d'attente
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
        curl_exec($ch);
    
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        curl_close($ch);
    
        return $httpCode == 200;
    }
}