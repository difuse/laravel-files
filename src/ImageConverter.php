<?php

namespace Helori\LaravelFiles;

use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\Process\Process;


class ImageConverter
{
    /**
     * A4 DPI width factor
     * @var  string $dpiA4WidthFactor
     */
    protected static $dpiA4WidthFactor = 8.26666667;

    /**
     * A4 DPI height factor
     * @var  string $dpiA4HeightFactor
     */
    protected static $dpiA4HeightFactor = 11.69333333;

    /**
     * Convert image to another image format
     * Intervention\Image does a great job here => no need to re-invent the wheel
     *
     * @param  string $srcPath The absolute path of the image to convert.
     * @param  string $tgtPath The path of the resulting image. The extension will be used as the conversion format.
     * @return boolean true on success
     */
    public static function convertImage(string $srcPath, string $tgtPath)
    {
        // configure with favored image driver (gd by default)
        Image::configure(['driver' => 'imagick']);
        Image::make($srcPath)->save($tgtPath);

        return true;
    }

    /**
     * Convert image to PDF
     *
     * @param  string $imgPath The absolute path of the image to convert.
     * @param  string $pdfPath The path of the resulting PDF
     * @param  array $options List of options for the resulting PDF
     * @return boolean true on success
     */
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

        $image = Image::make($imgPath)->resize($w, null, function($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('jpg', 75);

        $newPath = storage_path('tmp/'.Str::random(15).'.jpg');
        $image->save($newPath);

        $args = [
            'convert',
            '-units', 'PixelsPerInch',
            '-density', $opts['dpi'],
            '-flatten',
            '-quality', '100',
            '-compress', 'Zip',
            '-extent', $w.'x'.$h,
            '-resize', $w.'x'.$h,// keeps aspect ratio (result is contained in the box)
            '-gravity', 'center',
            '-border', $m,
            '-bordercolor', 'white',
            '-background', 'white',
            //'-page', 'a4',
            $imgPath,
            $pdfPath,
        ];

        $process = new Process($args);
        $process->mustRun();

        if(!is_file($pdfPath)){
            throw new \Exception("The file \"".$pdfPath."\" has not been created", 500);
        }

        return true;
    }

    /**
     * Convert PDF to image
     *
     * @param  string $pdfPath The absolute path of the pdf to convert.
     * @param  string|null $imgPath The path of the resulting image. If null, a JPG image will be sent to STDOUT
     * @param  array $options List of options for the resulting image
     * @return boolean|binary true on success or image content if $imgPath is null
     */
    public static function convertPdfToImage(string $pdfPath, string $imgPath = null, array $options = [])
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

        $args = [
            'convert',
            '-units',
            'PixelsPerInch',
            '-density',
            $opts['dpi'],
            $pdfPath.'['.$opts['page'].']',
            '-flatten',
            '-quality',
            $opts['quality'],
        ];

        if($opts['trim']){
            $args[] = '-trim';
        }

        if(!is_null($imgPath)){

            $args[] = $imgPath;
            
            $process = new Process($args);
            $process->mustRun();
            
            if(!is_file($imgPath)){
                throw new \Exception("The file \"".$imgPath."\" has not been created", 500);
            }
            return true;

        }else{

            $args[] = 'jpg:-';

            $process = new Process($args);
            $process->mustRun();

            return $process->getOutput();
        }
    }
}
