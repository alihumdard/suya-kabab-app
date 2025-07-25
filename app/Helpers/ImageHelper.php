<?php

namespace App\Helpers;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageHelper
{
    public static function saveImage($imagefile, $path)
    {
        $originalImage = $imagefile;

        // Create image manager with GD driver
        $manager = new ImageManager(new Driver());
        $myImage = $manager->read($originalImage->getRealPath());

        $originalPath = public_path() . '/' . $path . '/';
        $filename = rand(0, 100) . time() . '.' . $originalImage->getClientOriginalExtension();
        $myImage->save($originalPath . $filename);

        return $path . '/' . $filename;
    }

    public static function saveResizedImage($imagefile, $path, $width, $height)
    {
        $originalImage = $imagefile;

        // Create image manager with GD driver
        $manager = new ImageManager(new Driver());
        $myImage = $manager->read($originalImage->getRealPath());
        $myImage->resize($width, $height);

        $originalPath = public_path() . '/' . $path . '/';
        $filename = rand(0, 100) . time() . '.' . $originalImage->getClientOriginalExtension();
        $myImage->save($originalPath . $filename);

        return $path . '/' . $filename;
    }

    public static function saveImageFromApi($base64Image, $path)
    {
        $originalImage = base64_decode(str_replace('data:image/jpg;base64,', '', $base64Image));

        // Create image manager with GD driver
        $manager = new ImageManager(new Driver());
        $myImage = $manager->read($originalImage);
        // $myImage->resize(1024,768);

        $originalPath = public_path() . '/' . $path . '/';
        $filename = rand(0, 100) . time() . '.png';
        $myImage->save($originalPath . $filename);

        return $path . '/' . $filename;
    }

    public static function saveResizeImageFromApi($base64Image, $path, $width, $height)
    {
        $originalImage = base64_decode(str_replace('data:image/jpg;base64,', '', $base64Image));

        // Create image manager with GD driver
        $manager = new ImageManager(new Driver());
        $myImage = $manager->read($originalImage);
        $myImage->resize($width, $height);

        $originalPath = public_path() . '/' . $path . '/';
        $filename = rand(0, 100) . time() . '.png';
        $myImage->save($originalPath . $filename);

        return $path . '/' . $filename;
    }

    public function deleteImage($path)
    {
        $image_path = public_path() . $path;
        unlink($image_path);
    }


    // composer require intervention/image

    // in config/app.php add to $providers
    // Intervention\Image\ImageServiceProvider::class
    // add to aliases
    // 'Image' => Intervention\Image\Facades\Image::class

}

