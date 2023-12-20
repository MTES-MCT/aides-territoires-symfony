<?php

namespace App\Command\Import\Program;

use App\Command\Import\ImportCommand;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:program', description: 'Import program')]
class ProgramImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import program';
    protected string $commandTextEnd = '<Import program';

    protected function import($input, $output)
    {
        // ==================================================================
        // ta bleau périmetres
        // ==================================================================

        $perimeters = $this->managerRegistry->getRepository(Perimeter::class)->findAll();
        $perimetersById = [];
        foreach ($perimeters as $perimeter) {
            $perimetersById[$perimeter->getId()] = $perimeter;
        }
        unset($perimeters);

        // ==================================================================
        // PROGRAM
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('PROGRAM');

        // fichier
        $filePath = $this->findCsvFile('programs_program_');
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
        
        $sqlBase = "INSERT INTO `program`
                    (
                    `id`,
                    perimeter_id,
                    `name`,
                    slug,
                    description,
                    short_description,
                    logo,
                    time_create,
                    meta_description,
                    meta_title,
                    is_spotlighted
                    )
                    VALUES ";
        $sql = $sqlBase;
        $sqlParams = [];
        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);
        
        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                $sql .= "
                (
                    :id".$rowNumber.",
                    :perimeter_id".$rowNumber.",
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :description".$rowNumber.",
                    :short_description".$rowNumber.",
                    :logo".$rowNumber.",
                    :time_create".$rowNumber.",
                    :meta_description".$rowNumber.",
                    :meta_title".$rowNumber.",
                    :is_spotlighted".$rowNumber."
                ),";

                $sqlParams["id".$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["name".$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams["description".$rowNumber] = $this->stringOrNull((string) $cells[2]->getValue());
                $sqlParams["short_description".$rowNumber] = $this->stringOrNull((string) $cells[3]->getValue());
                $sqlParams["slug".$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams["logo".$rowNumber] = $this->stringOrNull((string) $cells[5]->getValue());
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[6]->getValue());
                $sqlParams["time_create".$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams["meta_description".$rowNumber] = $this->stringOrNull((string) $cells[7]->getValue());
                $sqlParams["meta_title".$rowNumber] = $this->stringOrNull((string) $cells[8]->getValue());
                $sqlParams["perimeter_id".$rowNumber] = $this->intOrNull((string) $cells[9]->getValue());                
                $sqlParams["is_spotlighted".$rowNumber] = $this->stringToBool((string) $cells[2]->getValue());

                if ($rowNumber % $nbByBatch == 0) {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);

                    $sqlParams = [];
                    $sql = $sqlBase;

                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                }
            }
        }


        if (count($sqlParams) > 0) {
            $sql = substr($sql, 0, -1);
            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
            $stmt->execute($sqlParams);
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // libère mémoire
        // ==================================================================

        unset($themesById);

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}