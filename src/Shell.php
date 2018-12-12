<?php

namespace Helori\LaravelFiles;


class Shell
{
    public static function runCommand(string $cmd, string $args, bool $throw = true)
    {
        if(!function_exists('exec')){
            throw new \Exception('The "exec" function cannot be executed on this server');
        }

        @exec($cmd.' '.$args, $output, $resultCode);

        if($resultCode !== 0){

            $message = implode(' | ', $output);

            if($throw){
                throw new \Exception($message, 500);    
            }else{
                return [
                    'code' => $resultCode,
                    'message' => $message,
                ];
            }
        }

        return true;
    }
}
