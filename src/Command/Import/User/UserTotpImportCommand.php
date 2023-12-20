<?php

namespace App\Command\Import\User;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:user_totp', description: 'Import users totp')]
class UserTotpImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import users totp';
    protected string $commandTextEnd = '<Import users totp';

    protected function import($input, $output)
    {
        // ==================================================================
        // USER TOTP
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('Users TOTP');

        // fichier
        $filePath = $this->findCsvFile('otp_totp_totpdevice_');
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

        // progressbar
        $io->createProgressBar($nbToImport);

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

                $sql = "
                    UPDATE user
                    SET totp_secret = :totp_secret
                    WHERE id = :id
                ";



                $sqlParams['id'] = (int) $cells[10]->getValue();
                $sqlParams['totp_secret'] = (string) $cells[3]->getValue();

                $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                $stmt->execute($sqlParams);

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}