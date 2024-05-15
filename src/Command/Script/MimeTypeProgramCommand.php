<?php

namespace App\Command\Script;

use App\Entity\Program\Program;
use App\Service\File\FileService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Aws\S3\S3Client;
use App\Service\Various\ParamService;
use Aws\Credentials\Credentials;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(name: 'at:script:mime_type_program_fix', description: 'Fix des mimes types sur s3')]
class MimeTypeProgramCommand extends Command
{

    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Fix des mimes types sur s3';
    protected string $commandTextEnd = '>Fix des mimes types sur s3';

    

    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ParamService $paramService,
        protected FileService $fileService
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try  {
            // import des keywords
            $this->fixMimesTypes($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function fixMimesTypes($input, $output): void
    {
        $io = new SymfonyStyle($input, $output);

        // Créer un objet Credentials en utilisant les clés d'accès AWS
        $credentials = new Credentials($this->paramService->get('aws_access_key_id'), $this->paramService->get('aws_secret_access_key'));

        // Créer un client S3
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => $this->paramService->get('aws_s3_region_name'),
            'endpoint' => $this->paramService->get('aws_s3_endpoint_url'),
            'credentials' => $credentials,
            'use_path_style_endpoint' => true
        ]);
        
        // recupere tous les programs
        $programs = $this->managerRegistry->getRepository(Program::class)->findBy(
            [],
            ['id' => 'DESC'],
        );
        foreach ($programs as $program) {
            if (!$program->getLogo()) {
                continue;
            }
            try {
                $result = $s3->getObject([
                    'Bucket' => $this->paramService->get('aws_storage_bucket_name'),
                    'Key'    => $program->getLogo(),
                ]);
        
                // le mimeType actuel
                $mimeType = $result['ContentType'];
                
                if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'])) {
                    // Vérifiez si l'objet est une image, un PDF, un CSV ou un JSON en fonction de son extension
                    $extension = pathinfo($program->getLogo(), PATHINFO_EXTENSION);
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                        // Déterminez le type MIME en fonction de l'extension
                        $mimeType = 'image/jpeg';
                        if ($extension == 'png') {
                            $mimeType = 'image/png';
                        } elseif ($extension == 'gif') {
                            $mimeType = 'image/gif';
                        } elseif ($extension == 'svg') {
                            $mimeType = 'image/svg+xml';
                        }
            
                        // Chemin vers le fichier temporaire
                        $url = $this->paramService->get('cloud_image_url').$program->getLogo();

                        // Télécharge le fichier à un emplacement temporaire
                        $tempPath = tempnam($this->fileService->getUploadTmpDir(), '');
                        file_put_contents($tempPath, file_get_contents($url));

                        // Nom original du fichier tel que fourni par le client
                        $originalName = basename($program->getLogo());


                        // Taille du fichier en octets
                        $size = null;

                        // Code d'erreur, utilisez UPLOAD_ERR_OK pour indiquer qu'il n'y a pas d'erreur
                        $error = UPLOAD_ERR_OK;

                        $file = new UploadedFile($tempPath, $originalName, $mimeType, $size, $error);

                        // re-upload l'objet sur lui même
                        $s3->putObject([
                            'Bucket' => $this->paramService->get('aws_storage_bucket_name'),
                            'Key'    => $program->getLogo(),
                            'SourceFile' => $file,
                            'ACL'    => 'public-read',
                            'ContentType' => $mimeType
                        ]);

                        $io->success("Fixed MIME type for {$program->getLogo()}");
                    } else {
                        $io->warning("{$program->getLogo()} dans aucun type myme reconnu");
                    }
                } else {
                    $io->success("{$program->getLogo()} à déjà {$mimeType}");
                }


            } catch (\Exception $e) {
                $io->error($program->getLogo());
                continue;
            }
        }
    }
}
