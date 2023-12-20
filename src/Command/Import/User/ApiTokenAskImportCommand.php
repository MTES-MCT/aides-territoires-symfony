<?php

namespace App\Command\Import\User;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:import:user:api_token_ask', description: 'Import user token api ask')]
class ApiTokenAskImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import user token api ask';
    protected string $commandTextEnd = '<Import user token api ask';

    protected function import($input, $output)
    {
        // ==================================================================
        // STOCKAGE POUR EVITER DOUBLONS
        // ==================================================================
        $userIds = [];

        // ==================================================================
        // USER API TOKEN ASK
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('USER API TOKEN ASK');

        // fichier
        $filePath = $this->findCsvFile('authtoken_token_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        
        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // batch
        $nbByBatch = 1;
        $nbBatch = ceil($nbToImport / $nbByBatch);
        
        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();

        $sqlBase = "INSERT IGNORE INTO `api_token_ask`
        (
        user_id,
        time_create,
        date_create,
        time_accept
        )
        VALUES ";

        $sql = $sqlBase;
        $sqlParams = [];

        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);

        ini_set('auto_detect_line_endings',TRUE);
        $rowNumber = 1;
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 4096, ';')) !== false) {
                if ($rowNumber == 1) {
                    $rowNumber++;
                    continue;
                }

                if (in_array($this->intOrNull((string) $data[2]), $userIds)) {
                    $rowNumber++;
                    continue;
                }
                $userIds[] = $this->intOrNull((string) $data[2]);
                $sql .= "
                (
                    :user_id".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :time_accept".$rowNumber."
                ),";

                $timeCreate = $this->stringToDateTimeOrNow((string) $data[1]);
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams['user_id'.$rowNumber] = $this->intOrNull((string) $data[2]);
                $sqlParams['time_accept'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');

                // assigne le token au user
                $sqlUpdateUser = 'UPDATE user SET api_token = :api_token where id = :id';
                $sqlUpdateUserParams = [
                    'api_token' => (string) $data[0],
                    'id' => (int) $data[2],
                ];
                $stmtUpdateUser = $this->managerRegistry->getManager()->getConnection()->prepare($sqlUpdateUser);
                $stmtUpdateUser->execute($sqlUpdateUserParams);


                try {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);
                    $sqlParams = [];
                    $sql = $sqlBase;
                    
                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                } catch (\Exception $e) {
                }

                

                $rowNumber++;
            }
            fclose($handle);
        }
        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // USER API TOKEN ASK - description & url service
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('USER API TOKEN ASK - description & url service');

        // fichier
        $filePath = $this->findCsvFile('accounts_user_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // batch
        $nbByBatch = 1;
        $nbBatch = ceil($nbToImport / $nbByBatch);
        
        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();

        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);
        
        ini_set('auto_detect_line_endings',TRUE);
        $rowNumber = 1;
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 4096, ';')) !== false) {
                if ($rowNumber == 1) {
                    $rowNumber++;
                    continue;
                }

                $id = (int) $data[0];
                $description = $this->stringOrNull((string) $data[29]);
                $urlService = $this->stringOrNull((string) $data[30]);
                if (!$description && !$urlService) {
                    $rowNumber++;
                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                    continue;
                }

                $sql = 'UPDATE api_token_ask SET description = :description, url_service = :url_service where user_id = :user_id';
                $sqlParams = [
                    'description' => $description,
                    'url_service' => $urlService,
                    'user_id' => $id,
                ];
                $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                $stmt->execute($sqlParams);

                // advances the progress bar 1 unit
                $io->progressAdvance();
                $rowNumber++;
            }
            fclose($handle);
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();


        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}