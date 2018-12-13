<?php

namespace Helori\LaravelFiles;


class Shell
{
    public static function runCommand(string $cmd, string $args, bool $throw = true, bool $returnOutput = false)
    {
        if($returnOutput && !function_exists('passthru')){
            throw new \Exception('The "passthru" function cannot be executed on this server');
        }
        if(!$returnOutput && !function_exists('exec')){
            throw new \Exception('The "exec" function cannot be executed on this server');
        }

        if($returnOutput){

            ob_start();
            passthru($cmd.' '.$args, $resultCode);
            $output = ob_get_contents();
            ob_end_clean();

        }else{

            @exec($cmd.' '.$args, $output, $resultCode);
        }

        if($resultCode !== 0){

            $message = is_array($output) ? implode(' | ', $output) : $output;

            if($throw){
                throw new \Exception($message, 500);    
            }
        }
        
        return [
            'code' => $resultCode,
            'output' => is_array($output) ? implode(' | ', $output) : $output,
        ];
    }
}
