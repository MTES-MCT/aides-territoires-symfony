<?php

namespace App\Service\File;

use Symfony\Component\HttpKernel\KernelInterface;

class FileService
{
    const UPLOAD_TMP_FOLDER = '/public/uploads/_tmp/';

    public function __construct(
        private KernelInterface $kernelInterface
    )
    {
    }

    public function getUploadTmpDirRelative(): string
    {
        // Pour scalingo
        if ($this->getEnvironment() == 'prod') {
            return '/tmp';
        } else {
            return self::UPLOAD_TMP_FOLDER;
        }
    }

    public function getUploadTmpDir(): string
    {
        // Pour scalingo
        if ($this->getEnvironment() == 'prod') {
            return $this->kernelInterface->getProjectDir().'/tmp';
        } else {
            return $this->kernelInterface->getProjectDir().self::UPLOAD_TMP_FOLDER;
        }
    }

    public function getEnvironment(): string
    {
        return $this->kernelInterface->getEnvironment();
    }
}