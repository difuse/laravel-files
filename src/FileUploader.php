<?php

namespace Helori\LaravelFiles;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Helori\LaravelFiles\ImageConverter;
use App\Http\Requests;
use Carbon\Carbon;


class FileUploader
{
    /**
     * Upload multiple files on the server to a specific location and return the files data.
     * The HTTP request must contain uploaded files which will be moved to the $docPath folder
     * The $config array can contain the following keys :
     * - $file-param : the request key prefix corresponding to the files (eg: "file-" for file-0, file-1, file-2, ...)
     * - $file-name : the file name to be used. If unset, the original file name will be used
     * - $file-name-prefix : the prefix to be added to the file name
     * - $file-name-suffix : the suffix to be added to the file name
     *
     * @param  Request $request
     * @param  string $docPath
     * @param  array $config
     * @return Collection Uploaded files data
     */
    public static function uploadFiles(Request $request, $docPath, array $config = [])
    {
        $fileParam = isset($config['file-param']) ? $config['file-param'] : 'file-';

        $items = collect([]);
        $i = 0;

        while($request->hasFile($fileParam.$i))
        {
            $item = self::uploadFile($request, $docPath, [
                'file-param' => $fileParam.$i
            ]);
            $items->push($item);
            ++$i;
        }
        return $items;
    }

    /**
     * Upload a request file on the server to a specific location and return the file parameters
     * The HTTP request must contain a file upload which will be moved to the $docPath folder
     * The $config array can contain the following keys :
     * - $file-param : the request key corresponding to the file
     * - $file-name : the file name to be used. If unset, the original file name will be used
     * - $file-name-prefix : the prefix to be added to the file name
     * - $file-name-suffix : the suffix to be added to the file name
     *
     * @param  Request $request
     * @param  string $docPath
     * @param  array $config
     * @return array Uploaded files data
     */
    public static function uploadFile(Request $request, string $docPath, array $config = [])
    {
        $fileParam = isset($config['file-param']) ? $config['file-param'] : 'file-0';
        $fileName = isset($config['file-name']) ? $config['file-name'] : null;
        $fileNamePrefix = isset($config['file-name-prefix']) ? $config['file-name-prefix'] : null;
        $fileNameSuffix = isset($config['file-name-suffix']) ? $config['file-name-suffix'] : null;

        if($request->hasFile($fileParam)){

            $file = $request->file($fileParam);
            if(!$file->isValid()){
                abort(422, "Invalid file");
            }
            
            // -----------------------------------------------------------
            //  Get uploaded file infos
            // -----------------------------------------------------------
            $file_ext = $file->guessExtension();

            if(is_null($fileName)){

                // Use uploaded file name if title has never been set :
                $uploaded_file_name = $file->getClientOriginalName();
                $fileName = substr($uploaded_file_name, 0, strripos($uploaded_file_name, '.'));
            }

            $slug = Str::slug($fileName, '-');
            if(!is_null($fileNamePrefix)){
                $slug = Str::slug($fileNamePrefix.'-'.$slug, '-');
            }
            if(!is_null($fileNameSuffix)){
                $slug = Str::slug($slug.'-'.$fileNameSuffix, '-');
            }
            $filenameExt = $slug.'.'.$file_ext;
            $filepath = $docPath.'/'.$filenameExt;
            $abspath = storage_path('app').'/'.$filepath;

            // -----------------------------------------------------------
            //  Move file
            // -----------------------------------------------------------
            $file->move(storage_path('app').'/'.$docPath, $filenameExt);

            // -----------------------------------------------------------
            //  Count pages
            // -----------------------------------------------------------
            $pages = 1;
            if(Str::contains(mime_content_type($abspath), 'pdf')) {
                //$imagick = new Imagick($abspath);
                //$pages = $imagick->getNumberImages();
            }

            // -----------------------------------------------------------
            //  Gather uploaded file data
            // -----------------------------------------------------------
            $item = [
                'created_at' => Carbon::now(),
                'title' => $fileName,
                'slug' => $slug,
                'filename' => $filenameExt,
                'filepath' => $filepath,
                'mime' => mime_content_type($abspath),
                'size' => filesize($abspath),
                'decache' => filemtime($abspath),
                //'pages' => $pages,
            ];

            return $item;
        
        }else{
            abort(422, 'No input file found for '.$file_param);
        }
    }

    public static function deleteFile(&$file)
    {
        $abspath = storage_path('app').'/'.$file->filepath;
        if(is_file($abspath)){
            unlink($abspath);
        }
    }

    public static function renameFile(string $filepath, string $newFilenameNoExt)
    {
        if(!Storage::has($filepath)){
            abort(404, "File not found");
        }

        $old_abspath = storage_path('app').'/'.$filepath;

        $infos = pathinfo($old_abspath);
        $ext = $infos['extension'];
        $filename = $infos['basename'];
        $absDir = $infos['dirname'];
        $relDir = substr($filepath, 0, strripos($filepath, $filename) - 1);
        
        $new_slug = Str::slug($newFilenameNoExt, '-');
        $new_filename = $new_slug.'.'.$ext;
        $new_filepath = $relDir.'/'.$new_filename;
        $new_abspath = storage_path('app').'/'.$new_filepath;

        rename($old_abspath, $new_abspath);

        return [
            'slug' => $new_slug,
            'filename' => $new_filename,
            'filepath' => $new_filepath,
        ];
    }

    protected static function absPath(string $filepath)
    {
        if(is_file($filepath)){

            // filepath is an absolute path
            return $filepath;

        }else if(Storage::has($filepath)){

            // filepath is a relative path
            return storage_path('app').'/'.$filepath;
        
        }else{
            abort(404, "File not found");
        }
    }

    public static function downloadOrOpenFile(string $filepath, string $filename, bool $forceDownload)
    {
        $abspath = self::absPath($filepath);

        $infos = pathinfo($abspath);
        $mime = mime_content_type($abspath);
        $ext = $infos['extension'];
        $browserCanDisplay = Str::contains($mime, ['image', 'pdf']);
        $filenameExt = ($filename ? $filename.'.'.$ext : null);

        if($browserCanDisplay && !$forceDownload){

            return response()->file($abspath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; '.$filenameExt,
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            ]);

        }else{

            return response()->download($abspath, $filenameExt, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            ]);
        }
    }

    public static function previewFile(string $filepath)
    {
        if(!Storage::has($filepath)){
            abort(404, "File not found");
        }

        $abspath = storage_path('app').'/'.$filepath;
        $mime = mime_content_type($abspath);
        $infos = pathinfo($abspath);
        $content = null;

        if(Str::contains($mime, 'image')) {

            $content = file_get_contents($abspath);

        }else if(Str::contains($mime, 'pdf')){

            $content = ImageConverter::convertPdfToImage($abspath, null, [
                'dpi' => 72,
                'quality' => 90,
            ]);
        }

        if(!is_null($content)){

            return response()->make($content, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; '.$infos['basename'],
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            ]);
        }
    }
}
