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
        $numPages = PdfUtilities::numPages($tgtPath);
        $this->assertTrue($result);
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
        
        // Flatten to a new file
        $result = PdfUtilities::flattenPdf($srcPath, $tgtPath);
        $this->assertTrue($result);
        $this->assertFileExists($tgtPath);

        // Flatten original file
        $size = filesize($srcPath);
        $result = PdfUtilities::flattenPdf($srcPath, null);
        clearstatcache(true, $srcPath);
        $this->assertTrue($result);
        $this->assertTrue($size !== filesize($srcPath));

        unlink($srcPath);
        unlink($tgtPath);
    }

    public function testCompressPdfFile()
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

    public function testCompressPdfData()
    {
        $fileDir = $this->fileDir();

        // Copy original file which will be modified
        copy($fileDir.'test2.pdf', $fileDir.'test2_source.pdf');
        $srcPath = $fileDir.'test2_source.pdf';
        $tgtPath = $fileDir.'test2_compressed.pdf';
        $size = filesize($srcPath);
        $content = file_get_contents($srcPath);

        // compress to a new file using content
        $result = PdfUtilities::compressPdfData($content, $tgtPath, 'screen');
        $this->assertFileExists($tgtPath);
        $this->assertTrue($size > filesize($tgtPath));
        $this->assertTrue(mime_content_type($tgtPath) === 'application/pdf');
        unlink($tgtPath);

        // compress to a new file using path and returning content
        $contentOut = PdfUtilities::compressPdfData($content, null, 'screen');
        file_put_contents($tgtPath, $contentOut);
        $this->assertFileExists($tgtPath);
        $this->assertTrue($size > filesize($tgtPath));
        $this->assertTrue(mime_content_type($tgtPath) === 'application/pdf');
        unlink($tgtPath);

        unlink($srcPath);
    }
}
