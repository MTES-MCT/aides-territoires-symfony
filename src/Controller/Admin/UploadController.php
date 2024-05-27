<?php

namespace App\Controller\Admin;

use App\Controller\FrontController;
use App\Service\File\FileService;
use App\Service\Image\ImageService;
use App\Service\Various\ParamService;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadController extends FrontController {
    #[Route('/admin/upload-image', name: 'app_admin_upload_image', options: ['expose' => true])]
    public function index
    (
        Request $request,
        SluggerInterface $slugger,
        FileService $fileService,
        ImageService $imageService,
        ParamService $paramService
    ): Response
    {
        $image = $request->files->get('image', null);
        if ($image) {
            $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            // this is needed to safely include the file name as part of the URL
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();
        }

        // si file, on la met dans le dossier temporaire
        if ($image) {
            $tmpFolder = $fileService->getUploadTmpDir();
            $image->move(
                $tmpFolder,
                $newFilename
            );
        }
        
        // gestion de l'image
        try {
            $imageService->sendImageToCloud(
                $fileService->getUploadTmpDir().'/'.$newFilename,
                'upload',
                'upload/'.$newFilename
            );
        } catch (FileException $e) {
            return new JsonResponse([
                'url' => null,
                'exception' => $e->getMessage()
            ]);
        }
        
        return new JsonResponse([
            'data' => [
                'link' => $paramService->get('cloud_image_url').'upload/'.$newFilename,
            ],
            'success' => true,
            'code' => 200
        ]);
    }


    public function imagickAutorotate(\Imagick $image) :\Imagick
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