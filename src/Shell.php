<?php

namespace Helori\LaravelFiles;


class Shell
{
    public static function runCommand(string $cmd, string $args)
    {
        if(!function_exists('exec')){
            throw new \Exception('The "exec" function cannot be executed on this server');
        }

        @exec($cmd.' '.$args, $output, $resultCode);

        if($resultCode !== 0){
            throw new \Exception(implode(' | ', $output), 500);
        }

        return true;
    }
}
