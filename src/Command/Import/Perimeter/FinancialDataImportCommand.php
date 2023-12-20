<?php

namespace App\Command\Import\Perimeter;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:financial_data', description: 'Import financial_data')]
class FinancialDataImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import financial_data';
    protected string $commandTextEnd = '<Import financial_data';

    protected function import($input, $output)
    {
        // ==================================================================
        // FINANCIAL DATA
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('FINANCIAL DATA');

        // fichier
        $filePath = $this->findCsvFile('geofr_financialdata_');
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

        $sqlBase = "INSERT INTO `financial_data`
        (
        `id`,
        perimeter_id,
        insee_code,
        `year`,
        population_strata,
        `aggregate`,
        main_budget_amount,
        display_order
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
                    :insee_code".$rowNumber.",
                    :year".$rowNumber.",
                    :population_strata".$rowNumber.",
                    :aggregate".$rowNumber.",
                    :main_budget_amount".$rowNumber.",
                    :display_order".$rowNumber."
                ),";

                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();    
                $sqlParams['insee_code'.$rowNumber] = $this->stringOrNull((string) $cells[1]->getValue());
                $sqlParams['year'.$rowNumber] = $this->intOrNull((string) $cells[2]->getValue());
                $sqlParams['population_strata'.$rowNumber] = $this->intOrNull((string) $cells[3]->getValue());
                $sqlParams['aggregate'.$rowNumber] = $this->stringOrNull((string) $cells[4]->getValue());
                $sqlParams['main_budget_amount'.$rowNumber] = (float) $cells[5]->getValue();
                $sqlParams['display_order'.$rowNumber] = $this->intOrNull((string) $cells[6]->getValue());
                $sqlParams['perimeter_id'.$rowNumber] = (int) $cells[7]->getValue();

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

        // sauvegarde
        if (count($sqlParams) > 0) {
            $sql = substr($sql, 0, -1);
            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
            $stmt->execute($sqlParams);
        }
        
        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}