<?php

namespace Helori\LaravelFiles;

use Symfony\Component\Process\Process;
use Imagick;


class PdfUtilities
{
    /**
     * Count number of pages
     * @param  string $filepath Absolute path PDF  of the resulting PDF file
     * @return int Number of pages in the PDF document
     */
    public static function numPages(string $filepath)
    {
        $pages = 1;
        if(strpos(mime_content_type($filepath), 'pdf') !== false) {
            $imagick = new Imagick($filepath);
            $pages = $imagick->getNumberImages();
        }
        return $pages;
    }

    /**
     * Get commonly used arguments to run a Ghostscript command
     * @return array gs arguments
     */
    private static function argsForGs()
    {
        return [
            'gs',
            // Disables character caching.  Useful only for debugging.
            '-dNOCACHE',
            // Quiet startup: suppress normal startup messages, and also do the equivalent of -dQUIET.
            '-q',
            // Exit after last file
            '-dBATCH',
            // Disables the prompt and pause at the end of each page.  This may be desirable for applications where another program is driving Ghostscript.
            '-dNOPAUSE',
            //Restricts  file operations the job can perform.  Strongly recommended for spoolers, conversion scripts or other sensitive environments where a badly written or malicious PostScript program code must be prevented from changing important files.
            //'-dSAFER',
            // Selects an alternate initial output device
            '-sDEVICE=pdfwrite',
        ];
    }

    /**
     * Combine multiple PDF files in a single temporary one
     * @param  array $inputFilepaths Array of absolute paths of the PDF files to combine
     * @param  string $targetPath Absolute path of the resulting PDF file, or null to return the file's content
     * @return boolean true on success
     */
    public static function combinePdfs(array $inputFilepaths, string $targetPath = null)
    {
        if(empty($inputFilepaths)){
            throw new \Exception("Cannot combine PDF files : empty file list", 500);
        }

        foreach($inputFilepaths as $inputFilepath){

            if(!is_file($inputFilepath)){

                throw new \Exception("Cannot combine PDF files : a listed file is missing", 500);

            }else if(mime_content_type($inputFilepath) !== 'application/pdf'){

                throw new \Exception("Cannot combine PDF files : input files must be all in PDF format", 500);
            }
        }

        $args = self::argsForGs();

        $args[] = '-sstdout=%stderr';
        $args[] = '-sOutputFile=-';
        foreach($inputFilepaths as $inputFilepath){
            $args[] = $inputFilepath;
        }

        $process = new Process($args);
        $process->mustRun();

        if(is_null($targetPath)){
            return $process->getOutput();
        }else{
            file_put_contents($targetPath, $process->getOutput());
        }
    }

    /**
     * Flatten a PDF (useful to ensure that a PDF is correctly encoded for further operations)
     *
     * @param  string $sourcePath Absolute path of the PDF file
     * @param  string|null $targetPath Absolute path of the resulting file (if null, the resulting data is returned)
     * @return void
     */
    public static function flattenPdfFile(string $sourcePath, string $targetPath = null)
    {
        self::validateSourcePath($sourcePath);
        $inputData = file_get_contents($sourcePath);
        return self::flattenPdfData($inputData, $targetPath);
    }

    /**
     * Flatten PDF data (useful to ensure that a PDF is correctly encoded for further operations)
     *
     * @param  string $inputData The input PDF data
     * @param  string|null $targetPath Absolute path of the resulting file (if null, the resulting data is returned)
     * @return void
     */
    public static function flattenPdfData(string $inputData, string $targetPath = null)
    {
        $args = self::argsForGs();

        // Do not break links
        $args[] = '-dPrinted=false';

        $args[] = '-sstdout=%stderr';
        $args[] = '-sOutputFile=-';
        $args[] = '-';

        $process = new Process($args);
        $process->setInput($inputData);
        $process->mustRun();

        if(is_null($targetPath)){
            return $process->getOutput();
        }else{
            file_put_contents($targetPath, $process->getOutput());
        }
    }

    /**
     * Rotate a PDF file
     *
     * @param  string $sourceFile Absolute path of the PDF file
     * @param  string|null $targetPath Absolute path of the resulting file. Can be same as input to replace . If null, the PDF content is returned.
     * @param  int $deg The angle to rotate. Allowed values are 90, 180, -90, 270
     * @param  int $page The page to totate, or null to rotate all pages
     * @return mixed PDF content or void, depending on $targetPath value
     */
    public static function rotatePdfFile(string $sourcePath, string $targetPath = null, int $deg = 90, ?int $page = null)
    {
        self::validateSourcePath($sourcePath);
        $inputData = file_get_contents($sourcePath);
        return self::rotatePdfData($inputData, $targetPath, $deg, $page);
    }

    /**
     * Rotate a PDF
     *
     * @param  string $inputData PDF input data
     * @param  string|null $targetPath Absolute path of the resulting file. Can be same as input to replace . If null, the PDF content is returned.
     * @param  int $deg The angle to rotate. Allowed values are 90, 180, -90, 270
     * @param  int $page The page to totate, or null to rotate all pages
     * @return mixed PDF content or void, depending on $targetPath value
     */
    public static function rotatePdfData(string $inputData, string $targetPath = null, int $deg = 90, ?int $page = null)
    {
        if(!in_array($deg, [90, 180, -90, 270])){
            throw new \Exception("Rotating from ".$deg." degrees is not allowed", 500);
        }

        $direction = '';
        if($deg === 90){
            $direction = 'right';
        }else if($deg === 180){
            $direction = 'down';
        }else if($deg === 270 || $deg === -90){
            $direction = 'left';
        }

        $pages = '1-end'; // All pages
        if(!is_null($page)){
            $pages = $page; // Single page
        }

        $args = [
            'pdftk',
            '-',
            'cat',
            $pages.$direction,
            'output',
            '-',
        ];

        $process = new Process($args);
        $process->setInput($inputData);
        $process->mustRun();

        if(is_null($targetPath)){
            return $process->getOutput();
        }else{
            file_put_contents($targetPath, $process->getOutput());
        }
    }

    /**
     * Compress a PDF file
     *
     * @param  string $sourceFile Absolute path of the PDF file
     * @param  string|null $targetPath Absolute path of the resulting file. Can be same as input to replace . If null, the PDF content is returned.
     * @param  string $mode Compression mode to use
     * @param  int $forceResolution The desired resolution (72, 150, 300, ...)
     * @return mixed PDF content or void, depending on $targetPath value
     */
    public static function compressPdfFile(string $sourcePath, string $targetPath = null, string $mode = 'ebook', ?int $forceResolution = null)
    {
        self::validateSourcePath($sourcePath);
        $inputData = file_get_contents($sourcePath);
        return self::compressPdfData($inputData, $targetPath, $mode, $forceResolution);
    }

    /**
     * Compress PDF data
     *
     * @param  string $inputData PDF input data
     * @param  string|null $targetPath Absolute path of the resulting file. Can be same as input to replace . If null, the PDF content is returned.
     * @param  string $mode Compression mode to use
     * @param  int $forceResolution The desired resolution (72, 150, 300, ...)
     * @return mixed PDF content or void, depending on $targetPath value
     */
    public static function compressPdfData(string $inputData, string $targetPath = null, string $mode = 'ebook', ?int $forceResolution = null)
    {
        $allowedModes = ['printer', 'screen', 'ebook', 'prepress', 'default'];
        if(!in_array($mode, $allowedModes)){
            throw new \Exception("Compression mode invalid. Choose one of the following : ".implode(', ', $allowedModes), 500);
        }

        $args = self::argsForGs();

        // Do not break links
        $args[] = '-dPrinted=false';

        // printer : selects output similar to the Acrobat Distiller "Print Optimized" setting.
        // screen : selects low-resolution output similar to the Acrobat Distiller "Screen Optimized" setting.
        // ebook : selects medium-resolution output similar to the Acrobat Distiller "eBook" setting.
        // prepress : selects output similar to Acrobat Distiller "Prepress Optimized" setting.
        // default : selects output intended to be useful across a wide variety of uses, possibly at the expense of a larger output file.
        $args[] = '-dPDFSETTINGS=/'.$mode;
        // Controls the automatic orientation selection algorithm : /None or /All or /PageByPage
        $args[] = '-dAutoRotatePages=/None';
        // Embed all fonts so that the PDF can be modified using them
        $args[] = '-dEmbedAllFonts=false';
        // Keep only a subset of used fonts so that the PDF can be visualized correctly
        $args[] = '-dSubsetFonts=true';

        $args[] = '-dCompatibilityLevel=1.4';

        /*if($blackAndWhite){
            $args .= ' -sProcessColorModel=DeviceGray';
            $args .= ' -sColorConversionStrategy=Gray';  
        }*/
        
        if($forceResolution){
            $args[] = '-dDownsampleColorImages=true';
            $args[] = '-dColorImageDownsampleType=/Average'; // /Subsample /Average /Bicubic
            $args[] = '-dColorImageResolution='.$forceResolution;

            $args[] = '-dDownsampleGrayImages=true';
            $args[] = '-dGrayImageDownsampleType=/Average';
            $args[] = '-dGrayImageResolution='.$forceResolution;
        }
        
        /*$args .= ' -dDownsampleMonoImages=true';
        $args .= ' -dMonoImageDownsampleType=/Subsample';
        $args .= ' -dMonoImageResolution=300';*/

        $args[] = '-sstdout=%stderr';
        $args[] = '-sOutputFile=-';
        $args[] = '-';

        $process = new Process($args);
        $process->setInput($inputData);
        $process->mustRun();

        if(is_null($targetPath)){
            return $process->getOutput();
        }else{
            file_put_contents($targetPath, $process->getOutput());
        }
    }

    /**
     * Validate input PDF file path
     *
     * @param  string $sourcePath The path of the source file.
     * @return void
     */
    private static function validateSourcePath(string $sourcePath)
    {
        if(!is_file($sourcePath)){
            throw new \Exception('The input PDF file does not exist');
        }else if(mime_content_type($sourcePath) !== 'application/pdf'){
            throw new \Exception('The input file must has the PDF mime type');
        }
    }
}
