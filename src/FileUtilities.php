<?php

namespace App\Utilities;

use ZipArchive;


class FileUtilities
{
    protected static $dpiA4WidthFactor = 8.26666667;
    protected static $dpiA4HeightFactor = 11.69333333;

    public static function convertImageToPdf(string $imgPath, string $pdfPath, array $options = [])
    {
        $opts = array_merge([
            'dpi' => 150,
            'marginWidthPercent' => 0,
        ], $options);

        if(!is_file($imgPath)){
            throw new \Exception("The file \"".$imgPath."\" doesn't exist", 500);
        }

        $mime = mime_content_type($imgPath);
        if(strpos($mime, 'image') !== 0){
            throw new \Exception("The file \"".$imgPath."\" is not an image", 500);
        }

        $marginWidthPercent = min(100, max(0, intVal($opts['marginWidthPercent']))) / 100;

        $w = self::$dpiA4WidthFactor * $opts['dpi'];
        $h = self::$dpiA4HeightFactor * $opts['dpi'];
        $m = 2 * ($w * $marginWidthPercent);

        $cmd = "convert";
        $args = "-background white";
        //$args .= " -page a4";
        $args .= " -units PixelsPerInch";
        $args .= " -density {$opts['dpi']}";
        $args .= " -bordercolor white";
        $args .= " -border {$m}";
        $args .= " -resize {$w}x{$h}"; // keeps aspect ratio (result is contained in the box)
        //$args .= " -repage {$w}x{$h}";
        $args .= " -gravity center";
        $args .= " -extent {$w}x{$h}";
        $args .= " -compress Zip";
        $args .= " -quality 100";
        $args .= " -flatten";
        $args .= " {$imgPath} {$pdfPath}";

        self::runCommand($cmd, $args);

        if(!is_file($pdfPath)){
            throw new \Exception("The file \"".$pdfPath."\" has not been created", 500);
        }

        return true;
    }

    public static function convertPdfToImage(string $pdfPath, string $imgPath, array $options = [])
    {
        $opts = array_merge([
            'page' => 0,
            'dpi' => 150,
            'quality' => 100,
            'trim' => false,
        ], $options);

        if(!is_file($pdfPath)){
            throw new \Exception("The file \"".$pdfPath."\" doesn't exist", 500);
        }

        $mime = mime_content_type($pdfPath);
        if($mime !== 'application/pdf'){
            throw new \Exception("The file \"".$pdfPath."\" is not a PDF", 500);
        }

        $pdfSafePath = escapeshellarg($pdfPath);
        $imgSafePath = escapeshellarg($imgPath);

        $cmd = "convert";
        $args = "-units PixelsPerInch";
        $args .= " -density {$opts['dpi']}";
        if($opts['trim']){
            $args .= " -trim";  
        }
        $args .= " {$pdfSafePath}[{$opts['page']}]";
        $args .= " -flatten";
        $args .= " -quality {$opts['quality']}";
        $args .= " {$imgSafePath}";

        self::runCommand($cmd, $args);

        if(!is_file($imgPath)){
            throw new \Exception("The file \"".$imgPath."\" has not been created", 500);
        }

        return true;
    }

    public static function optimizeImage(string $imgPath, string $targetPath, $quality = 100)
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

        self::runCommand($cmd, $args);

        return true;
    }

    /**
     * Combine multiple PDF files in a single temporary one
     * @param  array $inputFilepaths Array of absolute paths of the PDF to combine
     * @return string Absolute path of the resulting file
     */
    public static function combinePdfs(array $inputFilepaths)
    {
        $finalPath = sys_get_temp_dir().uniqId().'.pdf';

        $cmd = "gs";
        $args = "-q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$finalPath ";
        
        foreach($inputFilepaths as $filepath) {
            if(is_file($filepath)){
                $args .= $filepath." ";
            }
        }

        self::runCommand($cmd, $args);

        return $finalPath;
    }

    /**
     * Zip multiple files in a single temporary one
     * @param  array $inputFilepaths Array of absolute paths of the files to zip
     * @return string Absolute path of the resulting file
     */
    public static function zipFiles(array $inputFilepaths)
    {
        $finalPath = sys_get_temp_dir().uniqId().'.zip';

        $zip = new ZipArchive();
        $res = $zip->open($finalPath, ZipArchive::CREATE);
        if($res !== true){
            throw new \Exception('Could not create zip archive : '.$finalPath, 500);
        }

        foreach($inputFilepaths as $filepath){

            if(is_file($filepath)){
                $res = $zip->addFile($filepath);
                if($res !== true){
                    throw new \Exception("Could not add file to zip archive", 500);
                }
            }else{
                throw new \Exception("File not found", 500);
            }
        }
        $zip->close();

        return $finalPath;
    }

    public static function runCommand(string $cmd, string $args)
    {
        if(!function_exists('exec')){
            throw new \Exception('The "exec" function cannot be executed on this server');
        }

        @exec($cmd.' '.$args, $output, $resultCode);

        if($resultCode !== 0){
            throw new \Exception(implode(' | ', $output), 500);
        }

        return true;
    }
}
