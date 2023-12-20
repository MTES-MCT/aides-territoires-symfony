<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:log_aid_search_backer', description: 'Import log aid search backer')]
class LogAidSearchBackerImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log aid search backer';
    protected string $commandTextEnd = '<Import log aid search backer';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG AID SEARCH BACKER
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG AID SEARCH BACKER');

        // fichier
        $filePath = $this->findCsvFile('stats_aidsearchevent_backers_');
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
        $nbByBatch = 3000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();

        $sqlBase = "INSERT INTO `log_aid_search_backer`
        (
        log_aid_search_id,
        backer_id,
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
                    :log_aid_search_id".$rowNumber.",
                    :backer_id".$rowNumber."
                ),";

                $sqlParams['log_aid_search_id'.$rowNumber] = (int) $cells[1]->getValue();
                $sqlParams['backer_id'.$rowNumber] = (int) $cells[2]->getValue();

                if ($rowNumber % $nbByBatch == 0) {
                    try {
                        if (count($sqlParams) > 0) {
                            $sql = substr($sql, 0, -1);

                            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                            $stmt->execute($sqlParams);
                        }
                        $sqlParams = [];
                        $sql = $sqlBase;
                        
                        // advances the progress bar 1 unit
                        $io->progressAdvance();
                    } catch (\Exception $e) {
                    }

                }
            }
        }

        try {
            // sauvegarde
            if (count($sqlParams) > 0) {
                $sql = substr($sql, 0, -1);
                $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                $stmt->execute($sqlParams);
            }

        } catch (\Exception $e) {
        }
        // ensures that the progress bar is at 100%
        $io->progressFinish();
        $reader->close();

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}