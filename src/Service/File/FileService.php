<?php

namespace App\Service\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class FileService
{
    const FORMAT_CSV = 'csv';
    const FORMAT_XLSX = 'xlsx';
    const FORMAT_PDF = 'pdf';

    const UPLOAD_TMP_FOLDER = '/uploads_tmp';
    const EXCEPTION_FORMAT_NOT_SUPPORTED_MESSAGE = 'Format non supporté';

    const ENV_TEST = 'test';
    const ENV_DEV = 'dev';
    const ENV_PROD = 'prod';
    const ENV_STAGING = 'staging';
    
    public function __construct(
        private KernelInterface $kernelInterface
    ) {
    }

    public function getUploadTmpDirRelative(): string
    {
        // Pour scalingo
        if ($this->getEnvironment() == 'prod' || $this->getEnvironment() == 'staging') {
            return '';
        } else {
            return self::UPLOAD_TMP_FOLDER;
        }
    }

    public function getUploadTmpDir(): string
    {
        // Pour scalingo
        if ($this->getEnvironment() == 'prod' || $this->getEnvironment() == 'staging') {
            return $this->kernelInterface->getProjectDir();
        } else {
            return $this->kernelInterface->getProjectDir() . self::UPLOAD_TMP_FOLDER;
        }
    }

    public function getEnvironment(): string
    {
        return $this->kernelInterface->getEnvironment();
    }

    public function getProjectDir(): string
    {
        return $this->kernelInterface->getProjectDir();
    }

    public function getExtension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    public function uploadedFileIsImage(UploadedFile $file): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/svg+xml',
            'image/webp',
            'image/avif'
        ];

        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'svg',
            'webp',
            'avif'
        ];
    

        // Vérification du type MIME
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return false;
        }

        // Vérification de l'extension du fichier
        $extension = $file->getClientOriginalExtension();
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return false;
        }

        // Vérification avec getimagesize
        try {
            $imageSize = getimagesize($file->getPathname());
            if ($imageSize === false) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
