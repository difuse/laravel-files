<?php

namespace Helori\LaravelFiles;

use Helori\LaravelFiles\Shell;


class ImageOptimizer
{
    public static function optimize(string $imgPath, string $targetPath, $quality = 100)
    {
        $mimes = [
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/x-png' => 'png',
        ];

        $cmds_default = [
            'gif' => 'gifsicle',
            'jpg' => 'jpegoptim',
            'png' => 'pngquant',
        ];

        $opts_default = [
            'gifsicle' => '-b -O2 --interlace --output '.escapeshellarg($targetPath).' '.escapeshellarg($imgPath),
            'jpegoptim' => '-m'.$quality.' --strip-all --stdout '.escapeshellarg($imgPath).' > '.escapeshellarg($targetPath),
            'pngquant' => ' --force --output '.escapeshellarg($targetPath).' '.escapeshellarg($imgPath),
            'optipng' => '-o5 -clobber -strip all -out '.escapeshellarg($targetPath).' '.escapeshellarg($imgPath),
        ];

        $cmds = $cmds_default;
        $opts = $opts_default;

        $mime = mime_content_type($imgPath);

        if(!in_array($mime, array_keys($mimes))){
            throw new \Exception("Cannot optimize file with mime type $mime", 500);
        }
        
        $type = $mimes[$mime];
        $cmd = $cmds[$type];
        $args = $opts[$cmd];

        Shell::runCommand($cmd, $args);

        return true;
    }
}
