<?php

namespace Helori\LaravelFiles;

use ZipArchive;


class FileZipper
{
    /**
     * Zip files
     *
     * @param  array $inputFilepaths Array of absolute paths of the files to zip
     * @param  string $targetPath Resulting zip archive (must have a ".zip" extension)
     * @return boolean true on success
     */
    public static function zip(array $inputFilepaths, string $targetPath)
    {
        if(empty($inputFilepaths)){
            throw new \Exception("No file to zip", 500);
        }

        $zip = new ZipArchive();
        $res = $zip->open($targetPath, ZipArchive::CREATE);
        if($res !== true){
            throw new \Exception('Could not create zip archive', 500);
        }

        foreach($inputFilepaths as $filepath){

            if(is_file($filepath)){

                $filename = substr($filepath, strripos($filepath, '/'));
                $res = $zip->addFile($filepath, $filename);

                if($res !== true){
                    throw new \Exception("Adding existing file to zip archive failed : ".$filename, 500);
                }
            }else{
                throw new \Exception("Cannot add missing file to zip archive : ".$filename, 500);
            }
        }
        $zip->close();

        return true;
    }
}
