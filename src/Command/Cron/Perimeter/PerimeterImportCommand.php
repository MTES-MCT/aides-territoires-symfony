<?php

namespace App\Command\Cron\Perimeter;

use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use App\Service\Email\EmailService;
use App\Service\Various\ParamService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:cron:perimeter:perimeter_import', description: 'Import de périmètre adhoc')]
class PerimeterImportCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected EmailService $emailService,
        protected ParamService $paramService
    )
    {
        ini_set('max_execution_time', 60*60);
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

        $timeStart = microtime(true);

        $io = new SymfonyStyle($input, $output);

        try {
            $perimeterImport = $this->managerRegistry->getRepository(PerimeterImport::class)->find(88);
        
            // la demande d'import de périmètre adhoc la plus récente
            $perimeterImport = $this->managerRegistry->getRepository(PerimeterImport::class)->findNextToImport();
            if (!$perimeterImport instanceof PerimeterImport) {
                $io->error('Erreur : périmètre introuvable');
                return Command::SUCCESS;
            }
    
            if ($perimeterImport->isImportProcessing()) {
                $io->error('Erreur : périmètre déjà en cours d\'import');
                return Command::SUCCESS;
            }
    
            // passe l'import en cours de traitement
            $perimeterImport->setImportProcessing(true);
            $this->managerRegistry->getManager()->persist($perimeterImport);
            $this->managerRegistry->getManager()->flush();
    
            $io->info('Import de périmètre adhoc : '.$perimeterImport->getAdhocPerimeter()->getName().', auteur : '.$perimeterImport->getAuthor()->getEmail());
    
            $notFound = [];
            foreach ($perimeterImport->getCityCodes() as $cityCode) {
                $perimeterToAdd = $this->managerRegistry->getRepository(Perimeter::class)->findOneBy([
                    'code' => $cityCode
                ]);
                if (!$perimeterToAdd instanceof Perimeter) {
                    $notFound[] = $cityCode;
                    $io->warning('Périmètre introuvable avec ce code insee : '.$cityCode);
                    unset($perimeterToAdd);
                    continue;
                }
    
                // ajoute le périmètre correspondant au code insee
                $perimeterImport->getAdhocPerimeter()->addPerimetersFrom($perimeterToAdd);
    
                // va recuperer tous les parents du périmètre à ajouter et met le perimètre adhoc dedans,
                // ex: si perimetreToAdd = commune, alors on ajoute le perimetre adhoc dans le departement, la region, etc.
                foreach ($perimeterToAdd->getPerimetersTo() as $parentToAdd) {
                    if (
                        $parentToAdd->getId() !== $perimeterImport->getAdhocPerimeter()->getId() 
                        && $parentToAdd->getScale() <= Perimeter::SCALE_ADHOC
                    ) {
                    $perimeterImport->getAdhocPerimeter()->addPerimetersTo($parentToAdd);
                    }
                }
                unset($perimeterToAdd);
                // gc_collect_cycles();
            }
    
            // met à jour le perimetre import
            $perimeterImport->setIsImported(true);
            $perimeterImport->setAskProcessing(false);
            $perimeterImport->setImportProcessing(false);
            $perimeterImport->setTimeImported(new \DateTime(date('Y-m-d H:i:s')));
    
            // sauvegarde
            $this->managerRegistry->getManager()->persist($perimeterImport);
            $this->managerRegistry->getManager()->persist($perimeterImport->getAdhocPerimeter());
            $this->managerRegistry->getManager()->flush();
            
            // envoi un mail au créateur et au super admin
            $this->emailService->sendEmail(
                $perimeterImport->getAuthor()->getEmail(),
                'Import de périmètre adhoc terminé',
                'emails/admin/perimeter/perimeter_import.html.twig',
                [
                    'perimeterImport' => $perimeterImport,
                    'notFound' => $notFound
                ]
            );
            $this->emailService->sendEmail(
                $this->paramService->get('email_super_admin'),
                'Import de périmètre adhoc terminé',
                'emails/admin/perimeter/perimeter_import.html.twig',
                [
                    'perimeterImport' => $perimeterImport,
                    'notFound' => $notFound
                ]
            );
    
            $timeEnd = microtime(true);
            $time = $timeEnd - $timeStart;
    
            $io->success('Import terminé : '.gmdate("H:i:s", $timeEnd).' ('.gmdate("H:i:s", intval($time)).')');
            $io->success('Mémoire maximale utilisée : ' . intval(round(memory_get_peak_usage() / 1024 / 1024)) . ' MB');
    
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur : '.$e->getMessage());
            return Command::FAILURE;
        }
    }
}