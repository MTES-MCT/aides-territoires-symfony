<?php

namespace App\Command\Import\Blog;

use App\Command\Import\ImportCommand;
use App\Entity\Blog\BlogPost;
use App\Entity\Blog\BlogPostCategory;
use App\Repository\Blog\BlogPostCategoryRepository;
use App\Repository\Blog\BlogPostRepository;
use App\Repository\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'at:import:blog', description: 'Import blog')]
class BlogImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import blog';
    protected string $commandTextEnd = '<Import blog';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry,
        protected BlogPostRepository $blogPostRepository,
        protected BlogPostCategoryRepository $blogPostCategoryRepository,
        protected UserRepository $userRepository
    )
    {
        parent::__construct($kernelInterface, $managerRegistry);
    }

    protected function import($input, $output)
    {
        // recup des ids
        $usersById = [];

        $io = new SymfonyStyle($input, $output);
        $io->info('Catégories');
        // ==================================================================
        // CATEGORIES
        // ==================================================================

        // fichier
        $filePath = $this->findCsvFile('blog_blogpostcategory_');
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

                // entite
                $entity = new BlogPostCategory();
                $entity->setOldId((int) $cells[0]->getValue());
                $entity->setName((string) $cells[1]->getValue());
                $entity->setSlug((string) $cells[2]->getValue());
                $entity->setDescription((string) $cells[3]->getValue());
                try {
                    $entity->setTimeCreate(new \DateTime(date((string) $cells[4]->getValue())));
                } catch (\Exception $exception) {
                    $entity->setTimeCreate(new \DateTime(date('Y-m-d H:i:s')));
                }
                try {
                    $entity->setTimeUpdated(new \DateTime(date((string) $cells[5]->getValue())));
                } catch (\Exception $exception) {

                }

                // sauvegarde
                $this->managerRegistry->getManager()->persist($entity);
                $this->managerRegistry->getManager()->flush();

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        // refait les ids
        $this->blogPostCategoryRepository->importOldId();

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // POSTS
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('Posts');

        // tableau par id pour association des posts
        $categoriesById = [];
        $categories = $this->blogPostCategoryRepository->findAll();
        foreach ($categories as $category) {
            $categoriesById[$category->getId()] = $category;
        }

        // fichier
        $filePath = $this->findCsvFile('blog_blogpost_');
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

                // entite
                $entity = new BlogPost();
                $entity->setOldId((int) $cells[0]->getValue());
                $entity->setName((string) $cells[1]->getValue());
                $entity->setSlug((string) $cells[2]->getValue());
                $entity->setHat((string) $cells[3]->getValue());
                $entity->setDescription((string) $cells[4]->getValue());
                $entity->setLogo((string) $cells[5]->getValue() !== '' ? (string) $cells[5]->getValue() : null);
                $entity->setStatus((string) $cells[6]->getValue());
                $entity->setMetaTitle((string) $cells[7]->getValue() !== '' ? (string) $cells[7]->getValue() : null);
                $entity->setMetaDescription((string) $cells[8]->getValue() !== '' ? (string) $cells[8]->getValue() : null);
                
                try {
                    $entity->setTimeCreate(new \DateTime(date((string) $cells[9]->getValue())));
                } catch (\Exception $exception) {
                    $entity->setTimeCreate(new \DateTime(date('Y-m-d H:i:s')));
                }
                try {
                    $entity->setTimeUpdate(new \DateTime(date((string) $cells[10]->getValue())));
                } catch (\Exception $exception) {

                }
                try {
                    $entity->setDatePublished(new \DateTime(date((string) $cells[11]->getValue())));
                } catch (\Exception $exception) {

                }
                $entity->setBlogPostCategory($categoriesById[(int) $cells[12]->getValue()] ?? null);

                $usersById[(int) $cells[13]->getValue()] = $this->userRepository->find((int) $cells[13]->getValue()) ?? null;
                $entity->setUser($usersById[(int) $cells[13]->getValue()]);

                // sauvegarde
                $this->managerRegistry->getManager()->persist($entity);
                $this->managerRegistry->getManager()->flush();

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        // refait les ids
        $this->blogPostRepository->importOldId();

        // ensures that the progress bar is at 100%
        $io->progressFinish();


        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}