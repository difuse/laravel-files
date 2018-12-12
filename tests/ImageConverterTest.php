<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase
use Helori\LaravelFiles\ImageConverter;


class ImageConverterTest extends TestCase
{
    public function fileDir()
    {
        return dirname(__DIR__).'/Files/';
    }

    public function testJpgToPng()
    {
        $this->formatConvertion('jpg', 'png');
    }

    public function testJpgToGif()
    {
        $this->formatConvertion('jpg', 'gif');
    }

    public function testPngToJpg()
    {
        $this->formatConvertion('png', 'jpg');
    }

    public function testPngToGif()
    {
        $this->formatConvertion('png', 'gif');
    }

    public function testGifToJpg()
    {
        $this->formatConvertion('gif', 'jpg');
    }

    public function testGifToPng()
    {
        $this->formatConvertion('gif', 'png');
    }

    public function formatConvertion($fromExt, $toExt)
    {
        $fileDir = $this->fileDir();

        $srcPath = $fileDir.'test.'.$fromExt;
        $tgtPath = $fileDir.'test_converted.'.$toExt;

        $result = ImageConverter::convertImage($srcPath, $tgtPath);
        $this->assertTrue($result);
        $this->assertFileExists($tgtPath);
        
        unlink($tgtPath);
    }

    public function testJpgToPdf()
    {
        $this->toPdf('jpg');
    }

    public function testPngToPdf()
    {
        $this->toPdf('png');
    }

    public function toPdf($fromExt)
    {
        $fileDir = $this->fileDir();

        $srcPath = $fileDir.'test.'.$fromExt;
        $tgtPath = $fileDir.'test_converted.pdf';

        $result = ImageConverter::convertImageToPdf($srcPath, $tgtPath, [
            'dpi' => 72,
            'marginWidthPercent' => 5,
        ]);
        $this->assertTrue($result);
        $this->assertFileExists($tgtPath);
        
        unlink($tgtPath);
    }

    public function testPdfToJpg()
    {
        $this->fromPdf('jpg');
    }

    public function testPdfToPng()
    {
        $this->fromPdf('png');
    }

    public function fromPdf($toExt)
    {
        $fileDir = $this->fileDir();

        $srcPath = $fileDir.'test.pdf';
        $tgtPath = $fileDir.'test_converted.'.$toExt;

        $result = ImageConverter::convertPdfToImage($srcPath, $tgtPath, [
            'page' => 1, // Starts at 0
            'dpi' => 150,
            'quality' => 100,
        ]);
        $this->assertTrue($result);
        $this->assertFileExists($tgtPath);
        
        unlink($tgtPath);
    }
}
