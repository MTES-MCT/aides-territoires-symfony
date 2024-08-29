<?php

namespace App\Service\File;

use Symfony\Component\HttpKernel\KernelInterface;

class FileService
{
    const FORMAT_CSV = 'csv';
    const FORMAT_XLSX = 'xlsx';
    const FORMAT_PDF = 'pdf';
    
    const UPLOAD_TMP_FOLDER = '/public/uploads/_tmp';
    const EXCEPTION_FORMAT_NOT_SUPPORTED_MESSAGE = 'Format non supporté';
    
    public function __construct(
        private KernelInterface $kernelInterface
    )
    {
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
            return $this->kernelInterface->getProjectDir().self::UPLOAD_TMP_FOLDER;
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
}
