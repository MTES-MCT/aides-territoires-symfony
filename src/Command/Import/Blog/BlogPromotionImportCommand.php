<?php

namespace App\Command\Import\Blog;

use App\Command\Import\ImportCommand;
use App\Entity\Backer\Backer;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

#[AsCommand(name: 'at:import:blog_promotion', description: 'Import blog promotion')]
class BlogPromotionImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import blog promotion';
    protected string $commandTextEnd = '<Import blog promotion';

    protected function import($input, $output)
    {
        // ==================================================================
        // BLOG PROMOTION POST
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('BLOG PROMOTION POST');

        // fichier
        $filePath = $this->findCsvFile('blog_promotionpost_');
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

        $sqlBase = "INSERT INTO `blog_promotion_post`
        (
        `id`,
        perimeter_id,
        `name`,
        slug,
        short_text,
        button_link,
        button_title,
        `status`,
        time_create,
        time_update,
        `image`,
        image_alt_text,
        external_link
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


                $sql .= "
                (
                    :id".$rowNumber.",
                    :perimeter_id".$rowNumber.",
                    :name".$rowNumber.",
                    :slug".$rowNumber.",
                    :short_text".$rowNumber.",
                    :button_link".$rowNumber.",
                    :button_title".$rowNumber.",
                    :status".$rowNumber.",
                    :time_create".$rowNumber.",
                    :time_update".$rowNumber.",
                    :image".$rowNumber.",
                    :image_alt_text".$rowNumber.",
                    :external_link".$rowNumber."
                ),";

                $sqlParams['id'.$rowNumber] = $this->intOrNull((string) $data[0]);
                $sqlParams['name'.$rowNumber] = $this->stringOrNull((string) $data[1]);
                $sqlParams['slug'.$rowNumber] = $this->stringOrNull((string) $data[2]);
                $sqlParams['short_text'.$rowNumber] = $this->stringOrNull((string) $data[3]);
                $sqlParams['button_link'.$rowNumber] = $this->stringOrNull((string) $data[4]);
                $sqlParams['button_title'.$rowNumber] = $this->stringOrNull((string) $data[5]);
                $sqlParams['status'.$rowNumber] = $this->stringOrNull((string) $data[6]);
                $timeCreate = $this->stringToDateTimeOrNow((string) $data[7]);
                $sqlParams['time_create'.$rowNumber] = $timeCreate->format('Y-m-d H:i:s');
                $sqlParams['perimeter_id'.$rowNumber] = $this->intOrNull((string) $data[8]);
                $timeUpdate = $this->stringToDateTimeOrNull((string) $data[9]);
                $sqlParams['time_update'.$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;
                $sqlParams['image'.$rowNumber] = $this->stringOrNull((string) $data[10]);
                $sqlParams['image_alt_text'.$rowNumber] = $this->stringOrNull((string) $data[11]);
                $sqlParams['external_link'.$rowNumber] = $this->stringToBool((string) $data[12]);

                if ($rowNumber % $nbByBatch == 0) {
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
        // BLOG PROMOTION POST (LIAISONS)
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('BLOG PROMOTION POST (LIAISONS)');

        // fichier
        $filePath = $this->findCsvFile('blog_promotionpost_');
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

                $entity = $this->managerRegistry->getRepository(BlogPromotionPost::class)->find((int) $cells[0]->getValue());
            
                $organizationTypeSlugs = $this->stringToArrayOrNull((string) $cells[13]->getValue());
                if (is_array($organizationTypeSlugs)) {
                    foreach ($organizationTypeSlugs as $organizationTypeSlug) {
                        $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy(['slug' => $organizationTypeSlug]);
                        if ($organizationType instanceof OrganizationType) {
                            $entity->addOrganizationType($organizationType);
                        }
                    }
                }

                $this->managerRegistry->getManager()->persist($entity);

                // progress bar
                $io->progressAdvance();
            }
        }

        $this->managerRegistry->getManager()->flush();
        
        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // BLOG PROMOTION POST LIAISONS BACKERS
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('BLOG PROMOTION POST LIAISONS BACKERS');

        // fichier
        $filePath = $this->findCsvFile('blog_promotionpost_backers_');
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

                $entity = $this->managerRegistry->getRepository(BlogPromotionPost::class)->find((int) $cells[1]->getValue());
                if ($entity instanceof BlogPromotionPost) {
                    $backer = $this->managerRegistry->getRepository(Backer::class)->find((int) $cells[2]->getValue());
                    if ($backer instanceof Backer) {
                        $entity->addBacker($backer);
                        $this->managerRegistry->getManager()->persist($entity);
                    }
                }

                // progress bar
                $io->progressAdvance();
            }
        }

        $this->managerRegistry->getManager()->flush();

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // BLOG PROMOTION POST LIAISONS CATEGORIES
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('BLOG PROMOTION POST LIAISONS CATEGORIES');

        // fichier
        $filePath = $this->findCsvFile('blog_promotionpost_categories_');
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

                $entity = $this->managerRegistry->getRepository(BlogPromotionPost::class)->find((int) $cells[1]->getValue());
                if ($entity instanceof BlogPromotionPost) {
                    $category = $this->managerRegistry->getRepository(Category::class)->find((int) $cells[2]->getValue());
                    if ($category instanceof Category) {
                        $entity->addCategory($category);
                        $this->managerRegistry->getManager()->persist($entity);
                    }
                }

                // progress bar
                $io->progressAdvance();
            }
        }

        $this->managerRegistry->getManager()->flush();

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // BLOG PROMOTION POST LIAISONS PROGRAMS
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('BLOG PROMOTION POST LIAISONS PROGRAMS');

        // fichier
        $filePath = $this->findCsvFile('blog_promotionpost_programs_');
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

                $entity = $this->managerRegistry->getRepository(BlogPromotionPost::class)->find((int) $cells[1]->getValue());
                if ($entity instanceof BlogPromotionPost) {
                    $program = $this->managerRegistry->getRepository(Program::class)->find((int) $cells[2]->getValue());
                    if ($program instanceof Program) {
                        $entity->addProgram($program);
                        $this->managerRegistry->getManager()->persist($entity);
                    }
                }

                // progress bar
                $io->progressAdvance();
            }
        }

        $this->managerRegistry->getManager()->flush();

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}