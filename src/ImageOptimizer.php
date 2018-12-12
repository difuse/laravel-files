<?php

namespace Helori\LaravelFiles;

use Helori\LaravelFiles\Shell;


class ImageOptimizer
{
    /**
     * Compress image file
     * @param  string $imgPath The absolute path of the image to compress
     * @param  string|null $targetPath The path of the resulting image. If null, the original will be replaced
     * @param  int $quality The percentage of quality of the resulting image
     * @return boolean True on success
     */
    public static function optimize(string $imgPath, string $targetPath = null, int $quality = 100)
    {
        // Check if source file exists
        if(!is_file($imgPath)){
            throw new \Exception("The file doesn't exist", 500);
        }

        // Check if mime type corresponds to a file that can be optimized
        $mimes = [
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/x-png' => 'png',
        ];

        $mime = mime_content_type($imgPath);

        if(!in_array($mime, array_keys($mimes))){
            throw new \Exception("Cannot optimize image with mime type $mime", 500);
        }

        $type = $mimes[$mime];
        $result = false;

        if($type === 'jpg'){

            $result = self::optimizeJpg($imgPath, $targetPath, $quality);

        }else if($type === 'png'){

            $result = self::optimizePng($imgPath, $targetPath, $quality);

        }else if($type === 'gif'){

            $result = self::optimizeGif($imgPath, $targetPath);

        }

        return $result;
    }

    /**
     * Compress GIF file
     * @param  string $imgPath The absolute path of the image to compress
     * @param  string|null $targetPath The path of the resulting image. If null, the original will be replaced
     * @return boolean true on success
     */
    public static function optimizeGif(string $imgPath, string $targetPath = null)
    {
        // Check if source file exists
        if(!is_file($imgPath)){
            throw new \Exception("The file doesn't exist", 500);
        }

        // Check if mime type corresponds to a jpeg file
        $mimes = [
            'image/gif',
        ];

        $mime = mime_content_type($imgPath);

        if(!in_array($mime, $mimes)){
            throw new \Exception("The mime type $mime doesn't correspond to a GIF file", 500);
        }

        // Turn interlacing on
        $args = ' --interlace';
        // Optimize output GIF animations for space. Level determines how much optimization is done; higher levels take longer, but may have better results. There are currently three levels
        // 1 : Stores only the changed portion of each image. This is the default.
        // 2 : Also uses transparency to shrink the file further. 
        // 3 : Try several optimization methods (usually slower, sometimes better results)
        $args .= ' --optimize=2';
        
        if(!is_null($targetPath)){

            // Send output to file. The special filename ‘-’ means the standard output
            $args .= ' --output '.escapeshellarg($targetPath);

        }else{

            // Modify each GIF input in place by reading and writing to the same filename
            $args .= ' --batch';
        }

        $args .= ' '.escapeshellarg($imgPath);

        Shell::runCommand('gifsicle', $args);

        return true;
    }

    /**
     * Compress PNG file
     * @param  string $imgPath The absolute path of the image to compress
     * @param  string|null $targetPath The path of the resulting image. If null, the original will be replaced
     * @param  int $quality The percentage of quality of the resulting image
     * @return boolean true on success
     */
    public static function optimizePng(string $imgPath, string $targetPath = null, int $quality = 100)
    {
        // Check if source file exists
        if(!is_file($imgPath)){
            throw new \Exception("The file doesn't exist", 500);
        }

        // Check if mime type corresponds to a jpeg file
        $mimes = [
            'image/png',
            'image/x-png',
        ];

        $mime = mime_content_type($imgPath);

        if(!in_array($mime, $mimes)){
            throw new \Exception("The mime type $mime doesn't correspond to a PNG file", 500);
        }

        // Remove optional chunks (metadata) from PNG files.
        $args = ' --strip';
        // Overwrite existing output files. “--ext .png --force” can be used to convert files in place (which is unsafe).
        $args .= ' --force';
        // --quality min-max : min and max are numbers in range 0 (worst) to 100 (perfect), similar to JPEG. pngquant will use the least amount of colors required to meet or exceed the max quality. If conversion results in quality below the min quality the image won't be saved (or if outputting to stdin, 24-bit original will be output) and pngquant will exit with status code 99.
        $args .= ' --quality 0-'.$quality;

        if(!is_null($targetPath)){

            // Writes converted file to the given path. When this option is used only single input file is allowed.
            $args .= ' --output '.escapeshellarg($targetPath);
        
        }else{

            // File extension (suffix) to use for output files instead of the default ‘-fs8.png’ or ‘-or8.png’.
            $args .= ' --ext .png';
        }

        $args .= ' '.escapeshellarg($imgPath);

        Shell::runCommand('pngquant', $args);

        return true;
    }

    /**
     * Compress JPEG file
     * @param  string $imgPath The absolute path of the image to compress
     * @param  string|null $targetPath The path of the resulting image. If null, the original will be replaced
     * @param  int $quality The percentage of quality of the resulting image
     * @return boolean True on success
     */
    public static function optimizeJpg(string $imgPath, string $targetPath = null, int $quality = 100)
    {
        // Check if source file exists
        if(!is_file($imgPath)){
            throw new \Exception("The file doesn't exist", 500);
        }

        // Check if mime type corresponds to a jpeg file
        $mimes = [
            'image/jpg',
            'image/jpeg',
            'image/pjpeg',
        ];

        $mime = mime_content_type($imgPath);

        if(!in_array($mime, $mimes)){
            throw new \Exception("The mime type $mime doesn't correspond to a JPEG file", 500);
        }

        $args = '-m'.$quality;
        // Strip all markers from output file. (NOTE! by default only Comment & Exif/IPTC/PhotoShop/ICC/XMP markers are kept, everything else is discarded)
        $args .= ' --strip-all';
        // Strip EXIF markers from output file.
        $args .= ' --strip-exif';
        // Strip IPTC / Adobe Photoshop (APP13) markers from output file.
        $args .= ' --strip-iptc';
        // Strip ICC profiles from output file.
        $args .= ' --strip-icc';
        // Strip XMP profiles from output file.
        $args .= ' --strip-xmp';

        if(is_null($targetPath)){

            $args .= ' '.escapeshellarg($imgPath);

        }else{

            // Force optimization, even if the result would be larger than the original file.
            $args .= ' --force';
            // Send output image to standard output. Note, if optimization didn't create smaller file than the input file, then no output (image) is sent to standard output. (Option --force can be used to force output of image always, even if optimized image was not smaller than input).
            $args .= ' --stdout '.escapeshellarg($imgPath).' > '.escapeshellarg($targetPath);

            // Overwrite target file even if it exists (when using -d option).
            //$args .= ' --overwrite';
            // Sets alternative destination directory where to save optimized files (default is to overwrite the originals). Please note that unchanged files won't be added to the destination directory. This means if the source file can't be compressed, no file will be created in the destination path.
            //$args .= ' --dest='.escapeshellarg($targetPath);
        }

        Shell::runCommand('jpegoptim', $args);

        return true;
    }
}
