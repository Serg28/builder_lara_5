<?php

namespace Vis\Builder;

class OptmizationImg
{
    public static function run($pathImg)
    {
        $infoImg = new \SplFileInfo($pathImg);
        $fullPathPicture = public_path().$pathImg;

        if (config('builder.optimization_img.active')) {
            $commandPng = config('builder.optimization_img.png_path');
            $commandJpg = config('builder.optimization_img.jpg_path');
            $commandGif = config('builder.optimization_img.gif_path');

            try {
                if ($infoImg->getExtension() == 'png') {
                    $commandPng = str_replace('[file]', $fullPathPicture, $commandPng);
                    exec($commandPng, $res);
                } elseif ($infoImg->getExtension() == 'jpg' || $infoImg->getExtension() == 'jpeg') {
                    $commandJpg = str_replace('[file]', $fullPathPicture, $commandJpg);
                    exec($commandJpg, $res);
                } /*elseif ($infoImg->getExtension() == 'gif') {
                    $commandGif = str_replace('[file]', $fullPathPicture, $commandGif);
                    exec($commandGif, $res);
                }*/
            } catch (\Exception $e) {
            }
        }

        if (config('builder.optimization_img.webp_optimize')) {
            /*try {
                $newFile = str_replace(['.png', '.jpg', '.jpeg'], '.webp', $fullPathPicture);

                $command = 'cwebp -q 80 '.$fullPathPicture.' -o '.$newFile;

                exec($command, $res);
            } catch (\Exception $e) {
            }*/

            try {
                $newFile = str_replace(['.png', '.jpg', '.jpeg', '.gif'], '.webp', $fullPathPicture);

                if (function_exists('imagewebp')) {
                    $image = null;
                    $fileExtension = pathinfo($fullPathPicture, PATHINFO_EXTENSION);

                    switch (strtolower($fileExtension)) {
                        case 'jpg':
                        case 'jpeg':
                            $image = imagecreatefromjpeg($fullPathPicture);
                            break;
                        case 'png':
                            $image = imagecreatefrompng($fullPathPicture);
                            break;
                        case 'gif':
                            $image = imagecreatefromgif($fullPathPicture);
                            break;
                        default:
                            // Логируем ошибку и выходим из блока try
                            //Log::error('Unsupported file format for WebP conversion: ' . $fullPathPicture);
                            break;
                    }

                    if ($image) {
                        if (strtolower($fileExtension) !== 'webp') {
                            if (imagewebp($image, $newFile, 80)) {
                                imagedestroy($image);
                            } else {
                                // Логируем ошибку, если не удалось создать WebP изображение
                                //Log::error('Failed to convert image to WebP: ' . $fullPathPicture);
                            }
                        }
                    } else {
                        // Логируем ошибку, если не удалось создать изображение из файла
                        //Log::error('Failed to create image from file: ' . $fullPathPicture);
                    }
                } else {
                    $command = 'cwebp -q 80 ' . $fullPathPicture . ' -o ' . $newFile;
                    exec($command, $res);
                }
            } catch (\Exception $e) {
            }
        }
    }
}
