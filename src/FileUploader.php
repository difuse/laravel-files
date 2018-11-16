<?php

namespace App\Utilities;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests;
use Carbon\Carbon;
use Imagick;


class FileUploader
{
    public static function uploadFiles(Request $request, $docPath)
    {
        $file_prefix = 'file-';
        $items = collect([]);
        $i = 0;

        while($request->hasFile($file_prefix.$i))
        {
            $item = $this->uploadFile($request, $docPath, [
                'file-param' => $file_prefix.$i
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
     * @return void
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

    public static function renameFile(&$file, $title, $doc_path)
    {
        if($title){

            $old_filename = $file->filename;
            $old_filepath = $file->filepath;
            $old_abspath = storage_path('app').'/'.$old_filepath;

            if(is_file($old_abspath))
            {
                $infos = pathinfo($old_abspath);
                $file_ext = $infos['extension'];

                $new_slug = Str::slug($file->id.'-'.$title, '-');
                $new_filename = $new_slug.'.'.$file_ext;
                $new_filepath = $doc_path.'/'.$new_filename;
                $new_abspath = storage_path('app').'/'.$new_filepath;

                rename($old_abspath, $new_abspath);

                return [
                    'title' => $title,
                    'slug' => $new_slug,
                    'filename' => $new_filename,
                    'filepath' => $new_filepath,
                ];

            }else{
                abort(404, 'Cound not rename file not found at path : '.$file->filepath);
            }
        }
        return [];
    }

    public static function openFile($filepath, $filename)
    {
        if(Storage::has($filepath)){

            $abspath = storage_path('app').'/'.$filepath;
            $infos = pathinfo($abspath);
            $mime = mime_content_type($abspath);
            $ext = $infos['extension'];

            return response()->make(file_get_contents($abspath), 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; '.($filename ? $filename.'.'.$ext : null),
            ]);
        }
        else{
            abort(404, "File not found");
        }
    }

    public static function downloadFile($filepath, $filename)
    {
        if(Storage::has($filepath)){

            $abspath = storage_path('app').'/'.$filepath;
            $infos = pathinfo($abspath);
            $ext = $infos['extension'];

            return response()->download($abspath, $filename ? $filename.'.'.$ext : null, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0'
            ]);
        }
        else{
            abort(404, "File not found");
        }
    }

    public static function previewFile(&$file)
    {
        if(Storage::has($file->filepath)){

            $abspath = storage_path('app').'/'.$file->filepath;
            $infos = pathinfo($abspath);
            $ext = $infos['extension'];

            $content = null;
            $mime = $file->mime;

            if(Str::contains($file->mime, 'image')) {

                $content = file_get_contents($abspath);

            }else if(Str::contains($file->mime, 'pdf')){

                $imagick = new Imagick();
                $imagick->setResolution(200, 200);
                $imagick->setColorspace(Imagick::COLORSPACE_RGB);
                $imagick->readImage(sprintf('%s[%s]', $abspath, 0));
                $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
                $imagick->setCompressionQuality(90);
                $imagick->setImageFormat('jpg');
                $imagick->setImageBackgroundColor('white');
                $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                
                $content = $imagick->getImageBlob();
            }

            if(!is_null($content)){
                return response()->make($content, 200, [
                    'Content-Type' => $file->mime,
                    'Content-Disposition' => 'inline; '.$file->title.'.'.$ext,
                ]);
            }

        }
        else{
            abort(404, "File not found");
        } 
    }
}
