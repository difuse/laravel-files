<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Helori\LaravelFiles\FileZipper;


class FileZipperTest extends TestCase
{
    public function fileDir()
    {
        return dirname(__DIR__).'/Files/';
    }

    public function testNumPages()
    {
        $fileDir = $this->fileDir();

        $tgtPath = $fileDir.'test.zip';
        
        $result = FileZipper::zip([
            $fileDir.'test.jpg',
            $fileDir.'test.png',
            $fileDir.'test.pdf',
            $fileDir.'test.gif',
        ], $tgtPath);

        $this->assertTrue($result);
        $this->assertFileExists($tgtPath);

        unlink($tgtPath);
    }
}
