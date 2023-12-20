<?php

namespace App\Command\Import\Perimeter;

use App\Command\Import\ImportCommand;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use App\Entity\User\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'at:import:perimeter_import', description: 'Import perimeter import')]
class PerimeterImportImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import perimeter import';
    protected string $commandTextEnd = '<Import perimeter import';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry
    )
    {
        parent::__construct($kernelInterface, $managerRegistry);
    }

    protected function import($input, $output)
    {
        // ==================================================================
        // Tableau par id pour la suite
        // ==================================================================

        // met tous les périmètres dans un tableau par id
        $perimeters = $this->managerRegistry->getRepository(Perimeter::class)->findAll();
        $perimetersById = [];
        foreach ($perimeters as $perimeter) {
            $perimetersById[$perimeter->getId()] = $perimeter;
        }

        // ==================================================================
        // Périmètres import
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('Périmètres import');

        $usersById = [];

        // fichier
        $filePath = $this->findCsvFile('geofr_perimeterimport_');
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
                if (isset($perimetersById[(int) $cells[6]->getValue()])) {
                    $entity = new PerimeterImport();
                    $entity->setCityCodes($this->stringToArrayOrNull((string) $cells[1]->getValue()));
                    $entity->setIsImported($this->stringToBool((string) $cells[2]->getValue()));
                    try {
                        $entity->setTimeImported(new \DateTime(date((string) $cells[3]->getValue())));
                    } catch (\Exception $exception) {
                    }
                    try {
                        $entity->setTimeCreate(new \DateTime(date((string) $cells[4]->getValue())));
                    } catch (\Exception $exception) {
                        $entity->setTimeCreate(new \DateTime(date('Y-m-d H:i:s')));
                    }
                    try {
                        $entity->setTimeUpdate(new \DateTime(date((string) $cells[5]->getValue())));
                    } catch (\Exception $exception) {
                    }
                    if (!isset($usersById[(int) $cells[7]->getValue()])) {
                        $usersById[(int) $cells[7]->getValue()] = $this->managerRegistry->getRepository(User::class)->find((int) $cells[7]->getValue());
                    }
                    if ($usersById[(int) $cells[7]->getValue()] instanceof User) {
                        $entity->setAuthor($usersById[(int) $cells[7]->getValue()]);
                    }
                    $perimetersById[(int) $cells[6]->getValue()]->addPerimeterImport($entity);
                    
                    // sauvegarde
                    $this->managerRegistry->getManager()->persist($perimetersById[(int) $cells[6]->getValue()]);
                }

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

        unset($perimetersById);

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}