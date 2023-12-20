<?php

namespace App\Controller\Admin;

use App\Controller\FrontController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadController extends FrontController {
    #[Route('/admin/upload-image', name: 'app_admin_upload_image', options: ['expose' => true])]
    public function index
    (
        Request $request,
        SluggerInterface $slugger,
        KernelInterface $kernelInterface
    ): Response
    {
        $image = $request->files->get('image', null);
        if ($image) {
            $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            // this is needed to safely include the file name as part of the URL
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();
        }

        // gestion de l'image
        try {
            // si file, on la met dans le dossier temporaire
            $tmpFile = null;
            if ($image) {
                $tmpFolder = $kernelInterface->getProjectDir().'/public/uploads/_tmp/';
                $image->move(
                    $tmpFolder,
                    $newFilename
                );
                $tmpFile = $tmpFolder.$newFilename;
            }

            $imagickSrc = $tmpFile;

            // ouvre l'image avec imagick
            $imagick = new \Imagick($imagickSrc);
            // auto-rotate
            $imagick = $this->imagickAutorotate($imagick);
            // format jpg par défaut
            $imagick->setImageFormat('jpg');
            // force resize pour nettoyer code malveillant
            $imagick->cropThumbnailImage($imagick->getImageWidth(), $imagick->getImageHeight());

            $imagick->writeImage($kernelInterface->getProjectDir().'/public/uploads/images/'.$newFilename);
        } catch (FileException $e) {
            return new JsonResponse([
                'url' => null,
                'exception' => $e->getMessage()
            ]);
        }
        
        return new JsonResponse([
            'data' => [
                'link' => $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL).'uploads/images/'.$newFilename
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