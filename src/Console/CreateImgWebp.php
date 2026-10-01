<?php

namespace Vis\Builder;

use Illuminate\Console\Command;

class CreateImgWebp extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'admin:createImgWebp';

    protected $signature = 'admin:createImgWebp {path=storage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create recursive webp img in folder public';

    private $foldersAll;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->argument('path');

        $baseFolder = public_path($path);

        $this->getFolders($baseFolder);

        foreach ($this->foldersAll as $folder) {
            $files = \File::files($folder);

            foreach ($files as $file) {
                $this->convert($file);
            }
        }
        $this->info('that all');
    }

    private function getFolders($folders)
    {
        $this->info($folders);

        $this->foldersAll[] = $folders;

        $folders = \File::directories($folders, true);

        if (!is_array($folders)) {
            return;
        }

        foreach ($folders as $folder) {
            if (strpos($folder, 'x')) {
                $this->foldersAll[] = $folder;

            }

            $this->getFolders($folder);
        }
    }

    private function convert($file)
    {
        $newFile = str_replace(['.png', '.jpg', '.jpeg'], '.webp', $file);

        /*if (!file_exists($newFile)) {
            $command = 'cwebp -q 80 '. $file .' -o '. $newFile;

            $this->info(' run: ' .$command);

            exec($command, $res);
        }*/


        if (!file_exists($newFile)) {
            if (function_exists('imagewebp')) {
                $this->info('Using imagewebp for conversion.');
                $image = null;
                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

                switch (strtolower($fileExtension)) {
                    case 'jpg':
                    case 'jpeg':
                        $image = imagecreatefromjpeg($file);
                        break;
                    case 'png':
                        $image = imagecreatefrompng($file);
                        break;
                    default:
                        $this->error('Unsupported file format.');
                        return;
                }

                if ($image) {
                    imagewebp($image, $newFile, 80);
                    imagedestroy($image);
                    $this->info('Image converted to WebP using imagewebp.');
                } else {
                    $this->error('Failed to create image from file.');
                }
            } else {
                $command = 'cwebp -q 80 ' . $file . ' -o ' . $newFile;
                $this->info(' run: ' . $command);
                exec($command, $res);
            }
        }

    }
}
