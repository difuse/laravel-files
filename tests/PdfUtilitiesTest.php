<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Helori\LaravelFiles\PdfUtilities;


class PdfUtilitiesTest extends TestCase
{
    public function fileDir()
    {
        return __DIR__.'/Files/';
    }

    public function testNumPages()
    {
        $fileDir = $this->fileDir();
        $srcPath = $fileDir.'test.pdf';
        $numPages = PdfUtilities::numPages($srcPath);
        $this->assertTrue($numPages === 2);
    }

    public function testCombinePdfs()
    {
        $fileDir = $this->fileDir();
        $tgtPath = $fileDir.'test_target.pdf';
        $srcPaths = [
            $fileDir.'test.pdf',
            $fileDir.'test.pdf',
        ];

        $result = PdfUtilities::combinePdfs($srcPaths, $tgtPath);
        $this->assertFileExists($tgtPath);
        $numPages = PdfUtilities::numPages($tgtPath);
        $this->assertTrue($numPages === 4);

        unlink($tgtPath);
    }

    public function testFlattenPdf()
    {
        $fileDir = $this->fileDir();

        // Copy original file which will be modified
        copy($fileDir.'test2.pdf', $fileDir.'test2_source.pdf');
        $srcPath = $fileDir.'test2_source.pdf';
        $tgtPath = $fileDir.'test2_flatten.pdf';
        $size = filesize($srcPath);
        
        // Flatten to a new file
        PdfUtilities::flattenPdfFile($srcPath, $tgtPath);
        $this->assertFileExists($tgtPath);
        $this->assertTrue($size !== filesize($tgtPath));

        // Flatten original file
        PdfUtilities::flattenPdfFile($srcPath, $srcPath);
        clearstatcache(true, $srcPath);
        $this->assertFileExists($srcPath);
        $this->assertTrue($size !== filesize($srcPath));

        unlink($srcPath);
        unlink($tgtPath);
    }

    public function testCompressPdf()
    {
        $fileDir = $this->fileDir();

        // Copy original file which will be modified
        copy($fileDir.'test2.pdf', $fileDir.'test2_source.pdf');
        $srcPath = $fileDir.'test2_source.pdf';
        $tgtPath = $fileDir.'test2_compressed.pdf';
        $size = filesize($srcPath);

        // compress to a new file using path
        $result = PdfUtilities::compressPdfFile($srcPath, $tgtPath, 'screen');
        $this->assertFileExists($tgtPath);
        $this->assertTrue($size > filesize($tgtPath));
        unlink($tgtPath);

        // compress to a new file using path and returning content
        $content = PdfUtilities::compressPdfFile($srcPath, null, 'screen');
        file_put_contents($tgtPath, $content);
        $this->assertFileExists($tgtPath);
        $this->assertTrue($size > filesize($tgtPath));
        $this->assertTrue(mime_content_type($tgtPath) === 'application/pdf');
        unlink($tgtPath);

        // compress original file using path
        $result = PdfUtilities::compressPdfFile($srcPath, $srcPath, 'screen');
        clearstatcache(true, $srcPath);
        $this->assertTrue($size > filesize($srcPath));
        unlink($srcPath);
    }

    public function testRotatePdf()
    {
        $fileDir = $this->fileDir();

        // Copy original file which will be modified
        copy($fileDir.'test2.pdf', $fileDir.'test2_source.pdf');
        $srcPath = $fileDir.'test2_source.pdf';
        $tgtPath = $fileDir.'test2_compressed.pdf';
        $size = filesize($srcPath);
        
        // rotate to a new file using path
        $result = PdfUtilities::rotatePdfFile($srcPath, $tgtPath, 90);
        $this->assertFileExists($tgtPath);
        $this->assertTrue(mime_content_type($tgtPath) === 'application/pdf');
        unlink($tgtPath);

        // rotate to a new file using path and returning content
        $content = PdfUtilities::rotatePdfFile($srcPath, null, 270);
        file_put_contents($tgtPath, $content);
        $this->assertFileExists($tgtPath);
        $this->assertTrue(mime_content_type($tgtPath) === 'application/pdf');
        unlink($tgtPath);

        // compress original file using path
        $result = PdfUtilities::rotatePdfFile($srcPath, $srcPath, 180);
        $this->assertFileExists($srcPath);
        $this->assertTrue(mime_content_type($srcPath) === 'application/pdf');
        unlink($srcPath);
    }
}
