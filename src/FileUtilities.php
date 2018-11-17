<?php

namespace Helori\LaravelFiles;

use ZipArchive;


class FileUtilities
{
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
}
