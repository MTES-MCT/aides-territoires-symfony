<?php

namespace App\Command\Import\Eligibility;

use App\Command\Import\ImportCommand;
use App\Entity\Eligibility\EligibilityQuestion;
use App\Entity\Eligibility\EligibilityTest;
use App\Entity\Eligibility\EligibilityTestQuestion;
use App\Entity\User\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'at:import:eligibility', description: 'Import eligibility')]
class EligibilityImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import eligibility';
    protected string $commandTextEnd = '<Import eligibility';

    protected function import($input, $output)
    {

        // ==================================================================
        // tableau users
        // ==================================================================
        $users = $this->managerRegistry->getRepository(User::class)->findAll();
        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
        }
        unset($users);

        // ==================================================================
        // eligibility test
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('eligibility test');

        // fichier
        $filePath = $this->findCsvFile('eligibility_eligibilitytest_');
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
                $entity = new EligibilityTest();
                $entity->setName((string) $cells[1]->getValue());
                $entity->setIntroduction((string) $cells[2]->getValue());
                $entity->setConclusion((string) $cells[3]->getValue());
                try {
                    $entity->setTimeCreate(new \DateTime(date((string) $cells[4]->getValue())));
                } catch (\Exception $exception) {
                    $entity->setTimeCreate(new \DateTime(date('Y-m-d H:i:s')));
                }
                try {
                    $entity->setTimeUpdate(new \DateTime(date((string) $cells[5]->getValue())));
                } catch (\Exception $exception) {
                }
                $entity->setAuthor((isset($usersById[(int) $cells[6]->getValue()])) ? $usersById[(int) $cells[6]->getValue()] : null);
                $entity->setConclusionFailure((string) $cells[7]->getValue());
                $entity->setConclusionSuccess((string) $cells[8]->getValue());
                
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
        // eligibility question
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('eligibility question');

        // fichier
        $filePath = $this->findCsvFile('eligibility_eligibilityquestion_');
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
                $entity = new EligibilityQuestion();
                $entity->setDescription((string) $cells[1]->getValue());
                $entity->setAnswerChoiceA((string) $cells[2]->getValue());
                $entity->setAnswerChoiceB($this->stringOrNull((string) $cells[3]->getValue()));
                $entity->setAnswerChoiceC($this->stringOrNull((string) $cells[4]->getValue()));
                $entity->setAnswerChoiceD($this->stringOrNull((string) $cells[5]->getValue()));
                $entity->setAnswerCorrect((string) $cells[6]->getValue());
                try {
                    $entity->setTimeCreate(new \DateTime(date((string) $cells[7]->getValue())));
                } catch (\Exception $exception) {
                    $entity->setTimeCreate(new \DateTime(date('Y-m-d H:i:s')));
                }
                try {
                    $entity->setTimeUpdate(new \DateTime(date((string) $cells[8]->getValue())));
                } catch (\Exception $exception) {
                }
                $entity->setAuthor((isset($usersById[(int) $cells[9]->getValue()])) ? $usersById[(int) $cells[9]->getValue()] : null);

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
        // tableau tests
        // ==================================================================
        $tests = $this->managerRegistry->getRepository(EligibilityTest::class)->findAll();
        $testsById = [];
        foreach ($tests as $test) {
            $testsById[$test->getId()] = $test;
        }
        unset($tests);


        // ==================================================================
        // tableau questions
        // ==================================================================
        $questions = $this->managerRegistry->getRepository(EligibilityQuestion::class)->findAll();
        $questionsById = [];
        foreach ($questions as $question) {
            $questionsById[$question->getId()] = $question;
        }
        unset($questions);

        // ==================================================================
        // eligibility test / question
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('eligibility test / question');

        // fichier
        $filePath = $this->findCsvFile('eligibility_eligibilitytestquestion_');
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
                $entity = new EligibilityTestQuestion();
                $entity->setPosition((int) $cells[1]->getValue());
                $entity->setEligibilityQuestion($questionsById[(int) $cells[2]->getValue()]);
                $entity->setEligibilityTest($testsById[(int) $cells[3]->getValue()]);

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
        // libère mémoire
        // ==================================================================

        unset($usersById);
        unset($questionsById);
        unset($testsById);

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}