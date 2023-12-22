<?php

namespace App\Service\Image;

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
    )
    {
    }

    /**
     * Envoi une image sur le cloud
     *
     * @param UploadedFile $file
     * @param string $uploadDir
     * @param string $fileName
     * @return boolean
     */
    public function sendImageToCloud(
        UploadedFile $file,
        string $uploadDir,
        string $fileName
    ): bool
    {
        // Upload a publicly accessible file. The file size and type are determined by the SDK.
        try {
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
            $tmpFile = $tmpFolder . preg_replace('/' . preg_quote($uploadDir, '/') . '/', '', $fileName);

            // resize image avec \Imagick   
            $imagick = new \Imagick($tmpFile);
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
    public function getSafeFileName(string $filename) : string {
        return uniqid().'-'.$this->stringService->getSlug($filename);
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