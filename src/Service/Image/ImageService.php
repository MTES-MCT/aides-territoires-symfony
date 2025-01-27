<?php

namespace App\Service\Image;

use App\Exception\InvalidFileFormatException;
use App\Service\File\FileService;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Aws\Credentials\Credentials;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class ImageService
{
    public function __construct(
        protected ParamService $paramService,
        protected KernelInterface $kernelInterface,
        protected StringService $stringService,
        protected FileService $fileService
    ) {
    }

    /**
     * Envoi une image (deja uploadee / traitee sur le serveur) sur le cloud
     *
     * @param string $file
     * @param string $uploadDir
     * @param string $fileName
     * @return boolean
     */
    public function sendImageToCloud(
        string $file,
        string $uploadDir,
        string $fileName
    ): bool {
        if (!$file) {
            return false;
        }

        try {
            // resize image avec \Imagick
            $imagick = new \Imagick($file);
            if ($imagick->getImageMimeType() == 'image/svg+xml') {
                // Charge le fichier SVG comme un document XML
                $svg = simplexml_load_file($file);

                // Supprime les éléments script
                foreach ($svg->xpath('//script') as $script) {
                    unset($script[0]);
                }

                // Supprime les éléments de métadonnées
                foreach ($svg->xpath('//metadata') as $metadata) {
                    unset($metadata[0]);
                }

                // Écrit le document XML nettoyé dans le fichier
                file_put_contents($file, $svg->asXML());
            } else {
                $imagick = $this->imagickAutorotate($imagick);
                $maxWidth = 1024;
                $maxHeight = 1024;
                // image trop grande, on la reisze
                if ($imagick->getImageWidth() > $maxWidth || $imagick->getImageHeight() > $maxHeight) {
                    $imagick->resizeImage($maxWidth, $maxHeight, \Imagick::FILTER_LANCZOS, 1, true);
                } else {
                    // si pas besoin de rezie, on la remet au même format pour nettoyer les métadonnées
                    $imagick->cropThumbnailImage($imagick->getImageWidth(), $imagick->getImageHeight(), true);
                }
                $imagick->writeImage($file);
            }
            $imagick->clear();


            // Créer un objet Credentials en utilisant les clés d'accès AWS
            $credentials = new Credentials(
                $this->paramService->get('aws_access_key_id'),
                $this->paramService->get('aws_secret_access_key')
            );

            // Créer un client S3
            $s3 = new S3Client([
                'version' => 'latest',
                'region'  => $this->paramService->get('aws_s3_region_name'),
                'endpoint' => $this->paramService->get('aws_s3_endpoint_url'),
                'credentials' => $credentials,
                'use_path_style_endpoint' => true
            ]);

            // Télécharger le fichier sur S3
            $s3->putObject([
                'Bucket' => $this->paramService->get('aws_storage_bucket_name'),
                'Key'    => $fileName,
                'SourceFile' => $file,
                'ACL'    => 'public-read',
            ]);

            // suppression fichier temporaire
            unlink($file);
            return true;
        } catch (S3Exception $e) {
            return false;
        }
    }

    public function deleteImageFromCloud(
        string $fileName
    ): void {
        if (!$fileName || trim($fileName) == '') {
            return;
        }

        // Créer un objet Credentials en utilisant les clés d'accès AWS
        $credentials = new Credentials(
            $this->paramService->get('aws_access_key_id'),
            $this->paramService->get('aws_secret_access_key')
        );


        // Créer un client S3
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => $this->paramService->get('aws_s3_region_name'),
            'endpoint' => $this->paramService->get('aws_s3_endpoint_url'),
            'credentials' => $credentials,
            'use_path_style_endpoint' => true
        ]);

        // supprime le fichier sur S3
        $s3->deleteObject([
            'Bucket' => $this->paramService->get('aws_storage_bucket_name'),
            'Key'    => $fileName,
        ]);
    }

    /**
     * Envoi une image (UploadedFile) sur le cloud
     *
     * @param UploadedFile $file
     * @param string $uploadDir
     * @param string $fileName
     * @return boolean
     */
    public function sendUploadedImageToCloud(
        UploadedFile $file,
        string $uploadDir,
        string $fileName
    ): bool {
        // Upload a publicly accessible file. The file size and type are determined by the SDK.
        try {
            // verification image
            if (!$this->fileService->uploadedFileIsImage($file)) {
                throw new InvalidFileFormatException('Le fichier n\'est pas une image');
            }

            // créer dossier temporaire si besoin
            $tmpFolder = $this->fileService->getUploadTmpDir();
            if (!is_dir($tmpFolder)) {
                mkdir($tmpFolder, 0777, true);
            }

            // déplace le fichier dans le dossier temporaire
            $file->move(
                $tmpFolder,
                preg_replace('/' . preg_quote($uploadDir, '/') . '/', '', $fileName)
            );
            $tmpFile = rtrim($tmpFolder, '/')
                . '/'
                . ltrim(preg_replace('/' . preg_quote($uploadDir, '/') . '/', '', $fileName), '/');

            // resize image avec \Imagick
            $imagick = new \Imagick($tmpFile);
            if ($imagick->getImageMimeType() == 'image/svg+xml') {
                // Charge le fichier SVG comme un document XML
                $svg = simplexml_load_file($tmpFile);

                // Supprime les éléments script
                foreach ($svg->xpath('//script') as $script) {
                    unset($script[0]);
                }

                // Supprime les éléments de métadonnées
                foreach ($svg->xpath('//metadata') as $metadata) {
                    unset($metadata[0]);
                }

                // Écrit le document XML nettoyé dans le fichier
                file_put_contents($tmpFile, $svg->asXML());
            } else {
                $imagick = $this->imagickAutorotate($imagick);
                $maxWidth = 1024;
                $maxHeight = 1024;
                // image trop grande, on la reisze
                if ($imagick->getImageWidth() > $maxWidth || $imagick->getImageHeight() > $maxHeight) {
                    $imagick->resizeImage($maxWidth, $maxHeight, \Imagick::FILTER_LANCZOS, 1, true);
                } else {
                    // si pas besoin de rezie, on la remet au même format pour nettoyer les métadonnées
                    $imagick->cropThumbnailImage($imagick->getImageWidth(), $imagick->getImageHeight(), true);
                }
                $imagick->writeImage($tmpFile);
            }
            $imagick->clear();

            // Créer un objet Credentials en utilisant les clés d'accès AWS
            $credentials = new Credentials(
                $this->paramService->get('aws_access_key_id'),
                $this->paramService->get('aws_secret_access_key')
            );

            // Créer un client S3
            $s3 = new S3Client([
                'version' => 'latest',
                'region'  => $this->paramService->get('aws_s3_region_name'),
                'endpoint' => $this->paramService->get('aws_s3_endpoint_url'),
                'credentials' => $credentials,
                'use_path_style_endpoint' => true
            ]);

            // Télécharger le fichier sur S3
            $s3->putObject([
                'Bucket' => $this->paramService->get('aws_storage_bucket_name'),
                'Key'    => $fileName,
                'SourceFile' => $tmpFile,
                'ACL'    => 'public-read',
            ]);

            // suppression fichier temporaire
            unlink($tmpFile);
            return true;
        } catch (S3Exception $e) {
            return false;
        }
    }

    /**
     * Nettoye un nom de fichier
     *
     * @param string $filename
     * @return string
     */
    public function getSafeFileName(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return uniqid() . '-' . $this->stringService->getSlug($filename) . '.' . $extension;
    }

    /**
     * Autorotate de imagick
     * @param \Imagick $image
     * @return \Imagick
     */
    public function imagickAutorotate(\Imagick $image): \Imagick
    {
        switch ($image->getImageOrientation()) {
            case \Imagick::ORIENTATION_TOPLEFT:
                break;
            case \Imagick::ORIENTATION_TOPRIGHT:
                $image->flopImage();
                break;
            case \Imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateImage("#000", 180);
                break;
            case \Imagick::ORIENTATION_BOTTOMLEFT:
                $image->flopImage();
                $image->rotateImage("#000", 180);
                break;
            case \Imagick::ORIENTATION_LEFTTOP:
                $image->flopImage();
                $image->rotateImage("#000", -90);
                break;
            case \Imagick::ORIENTATION_RIGHTTOP:
                $image->rotateImage("#000", 90);
                break;
            case \Imagick::ORIENTATION_RIGHTBOTTOM:
                $image->flopImage();
                $image->rotateImage("#000", 90);
                break;
            case \Imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateImage("#000", -90);
                break;
            default: // Invalid orientation
                break;
        }
        $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
        return $image;
    }
}
