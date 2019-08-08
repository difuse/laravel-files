<?php

namespace Helori\LaravelFiles;


class Shell
{
    public static function runCommand(string $cmd, bool $returnOutput = false)
    {
        $start = microtime(true);

        if(!function_exists('passthru')){
            throw new \Exception('The "passthru" function cannot be executed on this server');
        }

        //if($returnOutput){

            ob_start();
            passthru($cmd, $resultCode);
            $output = ob_get_contents();
            ob_end_clean();

        /*}else{

            @exec($cmd, $output, $resultCode);
        }

        $output = is_array($output) ? implode(' | ', $output) : $output;*/

        $errors = null;
        if($resultCode !== 0){
            $errors = $output;
            throw new \Exception($errors, 500);    
        }

        $timeElapsedSecs = microtime(true) - $start;
        
        return [
            'code' => $resultCode,
            'output' => $output,
            'errors' => $errors,
            'duration' => $timeElapsedSecs,
        ];
    }

    public static function runCommandFromPipe(string $cmd, string $stdin = null)
    {
        $start = microtime(true);

        /*$outfile = tempnam(sys_get_temp_dir(), 'cmd');
        $errfile = tempnam(sys_get_temp_dir(), 'cmd');
        if($outfile === false || $errfile === false){
            throw new \Exception("Could not create temporary files");
        }*/

        $descriptorspec = [
            //0 => ['pipe', 'r'],  // stdin is a pipe that the child will read from
            //1 => ['file', $outfile, 'w'],  // stdout is a file that the child will write to
            //2 => ['file', $errfile, 'w'],  // stderr is a file that the child will write to
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        if(!is_null($stdin)){
            $descriptorspec[0] = ['pipe', 'r'];
        }

        $process = proc_open($cmd, $descriptorspec, $pipes);
        if($process === false){
            throw new \Exception("Cannot open process for command");
        }

        if(!is_null($stdin)){
            $result = fwrite($pipes[0], $stdin);
            if($result === false){
                throw new \Exception('Could not write input to command process');
            }
            fclose($pipes[0]);
        }
        
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        // It is important to close any pipes before calling proc_close in order to avoid a deadlock
        $resultCode = proc_close($process);

        //$output = file($outfile);
        //$errors = file($errfile);

        if($resultCode !== 0){
            throw new \Exception($errors);
        }

        $timeElapsedSecs = microtime(true) - $start;

        return [
            'code' => $resultCode,
            'output' => $output,
            'errors' => $errors,
            'duration' => $timeElapsedSecs,
        ];
    }
}
