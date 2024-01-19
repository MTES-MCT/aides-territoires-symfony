<?php

namespace App\Command\Import\Organization;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:organization_invitation', description: 'Import organization invitation')]
class OrganizationInvitationImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import organization invitation';
    protected string $commandTextEnd = '<Import organization invitation';

    protected function import($input, $output)
    {
        // ==================================================================
        // organization favorite projects
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('organization invitation');

        // fichier
        $filePath = $this->findCsvFile('accounts_user_');
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
        $nbByBatch = 5000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO `organization_invitation`
                    (
                    author_id,
                    guest_id,
                    time_create,
                    date_create,
                    time_accept,
                    date_accept,
                    organization_id,
                    firstname,
                    lastname,
                    email,
                    time_refuse,
                    date_refuse
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

                $idOrganization = (int) $cells[21]->getValue();
                if (!$idOrganization) {
                    continue;
                }
                
                // entite

                $sql .= "
                (
                    :author_id".$rowNumber.",
                    :guest_id".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :time_accept".$rowNumber.",
                    :date_accept".$rowNumber.",
                    :organization_id".$rowNumber.",
                    :firstname".$rowNumber.",
                    :lastname".$rowNumber.",
                    :email".$rowNumber.",
                    :time_refuse".$rowNumber.",
                    :date_refuse".$rowNumber."
                ),";

                $sqlParams['author_id'.$rowNumber] = (int) $cells[22]->getValue();
                $sqlParams['guest_id'.$rowNumber] = (int) $cells[0]->getValue();
                $timeCreate = $this->stringToDateTimeOrNow((string) $cells[23]->getValue());
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $timeAccept = $this->stringToDateTimeOrNull((string) $cells[24]->getValue());
                $sqlParams['time_accept'.$rowNumber] = $timeAccept ? $timeAccept->format('Y-m-d H:i:s') : null;
                $sqlParams['date_accept'.$rowNumber] = $timeAccept ? $timeAccept->format('Y-m-d') : null;
                $sqlParams['organization_id'.$rowNumber] = (int) $cells[21]->getValue();
                $sqlParams['firstname'.$rowNumber] = (string) $cells[12]->getValue();
                $sqlParams['lastname'.$rowNumber] = (string) $cells[5]->getValue();
                $sqlParams['email'.$rowNumber] = (string) $cells[4]->getValue();
                $sqlParams['time_refuse'.$rowNumber] = null;
                $sqlParams['date_refuse'.$rowNumber] = null;


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
        // success
        // ==================================================================
        $io->success('import ok');
    }

}