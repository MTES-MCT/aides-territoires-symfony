<?php

namespace App\Command\Import\Log;

use App\Command\Import\ImportCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:import:log_blog_promotion_post_display', description: 'Import log blog promotion post display')]
class LogBlogPromotionPostDisplayImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import log blog promotion post display';
    protected string $commandTextEnd = '<Import log blog promotion post display';

    protected function import($input, $output)
    {
        // ==================================================================
        // LOG BLOG PROMOTION POST DISPLAY
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LOG BLOG PROMOTION POST DISPLAY');

        // fichier
        $filePath = $this->findCsvFile('stats_promotiondisplayevent_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }

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

        $sqlBase = "INSERT INTO `log_blog_promotion_post_display`
        (
        blog_promotion_post_id,
        querystring,
        source,
        time_create,
        date_create
        )
        VALUES ";
        $sql = $sqlBase;
        $sqlParams = [];

        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);
        
        ini_set('auto_detect_line_endings',TRUE);
        $rowNumber = 1;
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($raw_string = fgets($handle)) !== false) {
                if ($rowNumber == 1) {
                    $rowNumber++;
                    continue;
                }
                // Parse the raw csv string: "1, a, b, c"
                $data = str_getcsv($raw_string, ';');

                $sql .= "
                (
                    :blog_promotion_post_id".$rowNumber.",
                    :querystring".$rowNumber.",
                    :source".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber."
                ),";

                $sqlParams['querystring'.$rowNumber] = $this->stringOrNull((string) $data[1]);
                $sqlParams['source'.$rowNumber] = $this->stringOrNull((string) $data[2]);
                $timeCreate = $this->stringToDateTimeOrNow((string) $data[3]);
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['date_create'.$rowNumber] = $timeCreate->format('Y-m-d');
                $sqlParams['blog_promotion_post_id'.$rowNumber] = $this->intOrNull((string) $data[4]);

                if ($rowNumber % $nbByBatch == 0) {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);
                    
                    $sqlParams = [];
                    $sql = $sqlBase;
                    
                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                }

                $rowNumber++;
            }
            fclose($handle);
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

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }
}