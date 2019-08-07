<?php

namespace Helori\LaravelFiles;

use Helori\LaravelFiles\Shell;
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

        // Disables character caching.  Useful only for debugging.
        $args = ' -dNOCACHE';
        // Quiet startup: suppress normal startup messages, and also do the equivalent of -dQUIET.
        $args .= ' -q';
        // Exit after last file
        $args .= ' -dBATCH';
        // Disables the prompt and pause at the end of each page.  This may be desirable for applications where another program is driving Ghostscript.
        $args .= ' -dNOPAUSE';
        //Restricts  file operations the job can perform.  Strongly recommended for spoolers, conversion scripts or other sensitive environments where a badly written or malicious PostScript program code must be prevented from changing important files.
        //$args .= ' -dSAFER';
        // Selects an alternate initial output device
        $args .= ' -sDEVICE=pdfwrite';

        if(is_null($targetPath)){

            // This option is useful, particularly with input from PostScript files that may print to stdout
            $args .= ' -sstdout=%stderr';
            // Write to stdout
            $args .= ' -sOutputFile=-';

        }else{

            // Selects an alternate output file for the initial output device
            $args .= ' -sOutputFile='.escapeshellarg($targetPath);
        }
            
        // Input files
        $args .= ' '.implode(" ", $inputFilepaths);

        if(is_null($targetPath)){

            $result = Shell::runCommand('gs '.$args);
            return $result['output'];

        }else{

            Shell::runCommand('gs '.$args);
            return true;
        }
    }

    /**
     * Flatten a PDF (useful to ensure that a PDF is correctly encoded for further operations)
     *
     * @param  string $sourceFile Absolute path of the PDF file
     * @param  string|null $targetPath Absolute path of the resulting file (if null, the input file is replaced)
     * @return boolean true on success
     */
    public static function flattenPdf(string $sourceFile, string $targetPath = null)
    {
        if(!is_file($sourceFile)){

            throw new \Exception("Source file is missing", 500);

        }else if(mime_content_type($sourceFile) !== 'application/pdf'){

            throw new \Exception("Cannot flatten PDF file : input file must be in PDF format", 500);
        }

        // Disables character caching.  Useful only for debugging.
        $args = ' -dNOCACHE';
        // Quiet startup: suppress normal startup messages, and also do the equivalent of -dQUIET.
        $args .= ' -q';
        // Exit after last file
        $args .= ' -dBATCH';
        // Disables the prompt and pause at the end of each page.  This may be desirable for applications where another program is driving Ghostscript.
        $args .= ' -dNOPAUSE';
        //Restricts  file operations the job can perform.  Strongly recommended for spoolers, conversion scripts or other sensitive environments where a badly written or malicious PostScript program code must be prevented from changing important files.
        //$args .= ' -dSAFER';
        // Selects an alternate initial output device
        $args .= ' -sDEVICE=pdfwrite';
        // Do not break links
        $args .= ' -dPrinted=false';

        if(is_null($targetPath)){

            // This option is useful, particularly with input from PostScript files that may print to stdout
            $args .= ' -sstdout=%stderr';
            // Write to stdout
            $args .= ' -sOutputFile=-';
        
        }else{

            // Selects an alternate output file (or pipe) for the initial output device
            $args .= ' -sOutputFile='.escapeshellarg($targetPath);
        }

        // Input file
        $args .= ' '.escapeshellarg($sourceFile);

        if(is_null($targetPath)){

            $result = Shell::runCommand('gs '.$args);
            file_put_contents($sourceFile, $result['output']);

        }else{

            Shell::runCommand('gs '.$args);
        }

        return true;
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
        $args = self::compressPdfArgs($mode, $forceResolution);
        return self::run($args, $sourcePath, null, $targetPath);
    }

    /**
     * Compress PDF data
     *
     * @param  string $sourceFile Absolute path of the PDF file
     * @param  string|null $targetPath Absolute path of the resulting file. Can be same as input to replace . If null, the PDF content is returned.
     * @param  string $mode Compression mode to use
     * @param  int $forceResolution The desired resolution (72, 150, 300, ...)
     * @return mixed PDF content or void, depending on $targetPath value
     */
    public static function compressPdfData(string $sourceData, string $targetPath = null, string $mode = 'ebook', ?int $forceResolution = null)
    {
        $args = self::compressPdfArgs($mode, $forceResolution);
        return self::run($args, null, $sourceData, $targetPath);
    }

    /**
     * Get GS arguments to compress a PDF
     *
     * @param  string $mode Compression mode to use
     * @param  int $forceResolution The desired resolution (72, 150, 300, ...)
     * @return string arguments
     */
    private static function compressPdfArgs(string $mode = 'ebook', ?int $forceResolution = null)
    {
        $allowedModes = ['printer', 'screen', 'ebook', 'prepress', 'default'];
        if(!in_array($mode, $allowedModes)){
            throw new \Exception("Compression mode invalid. Choose one of the following : ".implode(', ', $allowedModes), 500);
        }

        // Disables character caching. Useful only for debugging.
        $args = ' -dNOCACHE';
        // Quiet startup: suppress normal startup messages, and also do the equivalent of -dQUIET.
        $args .= ' -q';
        // Exit after last file
        $args .= ' -dBATCH';
        // Disables the prompt and pause at the end of each page.  This may be desirable for applications where another program is driving Ghostscript.
        $args .= ' -dNOPAUSE';
        // Selects an alternate initial output device
        $args .= ' -sDEVICE=pdfwrite';
        // Do not break links
        $args .= ' -dPrinted=false';

        // printer : selects output similar to the Acrobat Distiller "Print Optimized" setting.
        // screen : selects low-resolution output similar to the Acrobat Distiller "Screen Optimized" setting.
        // ebook : selects medium-resolution output similar to the Acrobat Distiller "eBook" setting.
        // prepress : selects output similar to Acrobat Distiller "Prepress Optimized" setting.
        // default : selects output intended to be useful across a wide variety of uses, possibly at the expense of a larger output file.
        $args .= ' -dPDFSETTINGS=/'.$mode;
        // Controls the automatic orientation selection algorithm : /None or /All or /PageByPage
        $args .= ' -dAutoRotatePages=/None';
        // Embed all fonts so that the PDF can be modified using them
        $args .= ' -dEmbedAllFonts=false';
        // Keep only a subset of used fonts so that the PDF can be visualized correctly
        $args .= ' -dSubsetFonts=true';

        $args .= ' -dCompatibilityLevel=1.4';

        /*if($blackAndWhite){
            $args .= ' -sProcessColorModel=DeviceGray';
            $args .= ' -sColorConversionStrategy=Gray';  
        }*/
        
        if($forceResolution){
            $args .= ' -dDownsampleColorImages=true';
            $args .= ' -dColorImageDownsampleType=/Average'; // /Subsample /Average /Bicubic
            $args .= ' -dColorImageResolution='.$forceResolution;

            $args .= ' -dDownsampleGrayImages=true';
            $args .= ' -dGrayImageDownsampleType=/Average';
            $args .= ' -dGrayImageResolution='.$forceResolution;
        }
        
        /*$args .= ' -dDownsampleMonoImages=true';
        $args .= ' -dMonoImageDownsampleType=/Subsample';
        $args .= ' -dMonoImageResolution=300';*/

        return $args;
    }

    /**
     * Run GS command
     *
     * @param  string $args The GS arguments to use.
     * @param  string $sourcePath The path of the source file, or null to use $sourceContent.
     * @param  string $sourceContent The PDF content to use as input.
     * @param  string $targetPath The path to write the result to, or null to return the PDF content.
     * @return string arguments
     */
    private static function run(string $args, ?string $sourcePath, ?string $sourceContent, ?string $targetPath)
    {
        // ------------------------------------------------------
        // Validate arguments
        // ------------------------------------------------------
        if(!is_null($sourcePath)){
            if(!is_file($sourcePath)){
                throw new \Exception('The input PDF file does not exist');
            }else if(mime_content_type($sourcePath) !== 'application/pdf'){
                throw new \Exception('The input file must has the PDF mime type');
            }
        }else if(is_null($sourceContent)){
            throw new \Exception('The input PDF content is null');
        }

        // ------------------------------------------------------
        // Set output file path, or '-' for stdout
        // ------------------------------------------------------
        
        // If no target path, output the content.
        // If sourcePath = targetPath, also output the content to overwrite the source file later.
        $sendOutput = is_null($targetPath) || ($sourcePath === $targetPath);

        if($sendOutput){
            // This option is useful, particularly with input from PostScript files that may print to stdout
            $args .= ' -sstdout=%stderr';
            // Write to stdout
            $args .= ' -sOutputFile=-';
            //$args .= ' -sOutputFile=%stdout';
        }else{
            // Selects an alternate output file (or pipe) for the initial output device
            $args .= ' -sOutputFile='.escapeshellarg($targetPath);
        }

        // ------------------------------------------------------
        // Set input file path, or '-' for stdin, then run
        // ------------------------------------------------------
        if(is_null($sourcePath)){
            $args .= ' -';
        }else{
            $args .= ' '.escapeshellarg($sourcePath);
        }

        // ------------------------------------------------------
        // Run the command (throws exception on error)
        // ------------------------------------------------------
        if(is_null($sourceContent)){
            $result = Shell::runCommand('gs '.$args, $sendOutput);
        }else{
            $result = Shell::runCommandFromPipe('gs '.$args, $sourceContent);
        }

        // ------------------------------------------------------
        // Send the result output, or write it to $targetPath
        // ------------------------------------------------------
        if(is_null($targetPath)){
            return $result['output'];
        }else if($targetPath === $sourcePath){
            file_put_contents($targetPath, $result['output']);
        }
    }
}
