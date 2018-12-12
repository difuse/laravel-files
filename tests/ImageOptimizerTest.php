<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Helori\LaravelFiles\ImageOptimizer;


class ImageOptimizerTest extends TestCase
{
    public function fileDir()
    {
        return __DIR__.'/Files/';
    }

    public function testOptimizeJpg()
    {
        $this->runTestsOnImageExt('jpg');
    }

    public function testOptimizePng()
    {
        $this->runTestsOnImageExt('png');
    }

    public function testOptimizeGif()
    {
        $this->runTestsOnImageExt('gif');
    }

    protected function runTestsOnImageExt(string $ext)
    {
        $fileDir = $this->fileDir();

        // Copy original file which will be modified
        copy($fileDir.'test.'.$ext, $fileDir.'test_source.'.$ext);

        // Define source and target file paths
        $srcPath = $fileDir.'test_source.'.$ext;
        $tgtPath = $fileDir.'test_target.'.$ext;

        // Compress the image as a new image
        $result = ImageOptimizer::optimize($srcPath, $tgtPath, 70);
        $this->assertTrue($result);
        $this->assertFileExists($tgtPath);

        // Store image atributes
        $size = filesize($srcPath);
        //$modifTime = filemtime($srcPath);

        // Compress the original image
        $result = ImageOptimizer::optimize($srcPath, null, 70);
        $this->assertTrue($result);

        //sleep(1);
        clearstatcache(true, $srcPath);

        // Check source image has been modified
        $this->assertTrue($size !== filesize($srcPath));
        //$this->assertTrue($modifTime !== filemtime($srcPath));

        // Delete test files
        unlink($srcPath);
        unlink($tgtPath);
    }    
}
