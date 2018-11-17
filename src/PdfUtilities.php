<?php

namespace Helori\LaravelFiles;

use Helori\LaravelFiles\Shell;
use Imagick;


class PdfUtilities
{
    protected static $dpiA4WidthFactor = 8.26666667;
    protected static $dpiA4HeightFactor = 11.69333333;

    /**
     * Count number of pages
     * @return string $filepath
     * @return string $disk
     */
    public static function pages(string $filepath, string $disk)
    {
        $pages = 1;
        if(strpos(mime_content_type($abspath), 'pdf') !== false) {
            $imagick = new Imagick($abspath);
            $pages = $imagick->getNumberImages();
        }
        return $pages;
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

        Shell::runCommand($cmd, $args);

        return $finalPath;
    }

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

        Shell::runCommand($cmd, $args);

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

        Shell::runCommand($cmd, $args);

        if(!is_file($imgPath)){
            throw new \Exception("The file \"".$imgPath."\" has not been created", 500);
        }

        return true;
    }
}
