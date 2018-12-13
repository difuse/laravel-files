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
     * @param  string $inputFilepaths Absolute path of the resulting PDF file
     * @return boolean true on success
     */
    public static function combinePdfs(array $inputFilepaths, string $targetPath)
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
        $args .= ' -dSAFER';
        // Selects an alternate initial output device
        $args .= ' -sDEVICE=pdfwrite';
        // Selects an alternate output file (or pipe) for the initial output device
        $args .= ' -sOutputFile='.escapeshellarg($targetPath);
        // Input files
        $args .= ' '.implode(" ", $inputFilepaths);

        Shell::runCommand('gs', $args);

        return true;
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
        $args .= ' -dSAFER';
        // Selects an alternate initial output device
        $args .= ' -sDEVICE=pdfwrite';

        if(is_null($targetPath)){

            // CANNOT USE "WRITE TO STDOUT" : Ghostscript needs to read from the source file 
            // after opening the target, so they cannot be the same file. 

            // Send output to standard output (requires "-q" option to be set)
            //$args .= ' -sOutputFile=-';
            // Input file replaced
            //$args .= ' '.escapeshellarg($sourceFile).' > '.escapeshellarg($sourceFile);

            // INSTEAD : write to temporary file, then replace input file
            $tmpFile = sys_get_temp_dir().uniqId().'.pdf';
            // Selects an alternate output file (or pipe) for the initial output device
            $args .= ' -sOutputFile='.escapeshellarg($tmpFile);
        
        }else{

            // Selects an alternate output file (or pipe) for the initial output device
            $args .= ' -sOutputFile='.escapeshellarg($targetPath);
        }

        // Input file
        $args .= ' '.escapeshellarg($sourceFile);

        Shell::runCommand('gs', $args);

        // Replace input file if required
        if(is_null($targetPath)){
            copy($tmpFile, $sourceFile);
        }

        return true;
    }
}
