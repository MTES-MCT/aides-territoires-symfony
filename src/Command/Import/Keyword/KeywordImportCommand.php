<?php

namespace App\Command\Import\Keyword;

use App\Command\Import\ImportCommand;
use App\Entity\Keyword\Keyword;
use App\Entity\Keyword\KeywordSynonymlist;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:keyword', description: 'Import keyword')]
class KeywordImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import keyword';
    protected string $commandTextEnd = '<Import keyword';

    protected function import($input, $output)
    {

        // ==================================================================
        // KEYWORD
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('KEYWORD');

        // fichier
        $filePath = $this->findCsvFile('keywords_keyword_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        // ouverture du fichier
        $options = new Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';
        $reader = new Reader($options);
        $reader->open($filePath);

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // batch
        $nbByBatch = 10000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                // entite
                $entity = new Keyword();
                $entity->setOldId((int) $cells[0]->getValue());
                $entity->setName((string) $cells[1]->getValue());
                $entity->setSlug((string) $cells[2]->getValue());
                try {
                    $entity->setTimeCreate(new \DateTime(date((string) $cells[3]->getValue())));
                } catch (\Exception $exception) {
                    $entity->setTimeCreate(new \DateTime(date('Y-m-d H:i:s')));
                }
                
                // sauvegarde
                $this->managerRegistry->getManager()->persist($entity);
                

                if ($rowNumber % $nbByBatch == 0) {
                    $this->managerRegistry->getManager()->flush();
                }

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        $this->managerRegistry->getManager()->flush();

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // refait les ids
        // ==================================================================
        
        $this->managerRegistry->getRepository(Keyword::class)->importOldId();

        // ==================================================================
        // KEYWORD SYNONYMLIST
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('KEYWORD SYNONYMLIST');

        // fichier
        $filePath = $this->findCsvFile('keywords_synonymlist_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        // ouverture du fichier
        $options = new Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';
        $reader = new Reader($options);
        $reader->open($filePath);

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // batch
        $nbByBatch = 10000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                // entite
                $entity = new KeywordSynonymlist();
                $entity->setOldId((int) $cells[0]->getValue());
                $entity->setName((string) $cells[1]->getValue());
                $entity->setSlug((string) $cells[2]->getValue());
                try {
                    $entity->setTimeCreate(new \DateTime(date((string) $cells[3]->getValue())));
                } catch (\Exception $exception) {
                    $entity->setTimeCreate(new \DateTime(date('Y-m-d H:i:s')));
                }
                $entity->setKeywordsList((string) $cells[4]->getValue());
                
                // sauvegarde
                $this->managerRegistry->getManager()->persist($entity);
                

                if ($rowNumber % $nbByBatch == 0) {
                    $this->managerRegistry->getManager()->flush();
                }

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        $this->managerRegistry->getManager()->flush();

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // refait les ids
        // ==================================================================
        
        $this->managerRegistry->getRepository(KeywordSynonymlist::class)->importOldId();

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}