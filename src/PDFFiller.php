<?php

namespace Helori\LaravelFiles;

use setasign\Fpdi\Fpdi;


class PdfFiller extends FPDI
{
    public $shiftX = 0;
    public $shiftY = 0;

    public function __construct($orientation, $unit, $format){
        
        parent::__construct($orientation, $unit, $format);
        
        $this->SetFillColor(245, 250, 100);
        $this->SetFont('Helvetica', '');
        $this->SetTextColor(30, 30, 30);
        $this->SetFontSize(8);
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false);
    }

    /**
     * Write text on the current page
     * 
     * @param  int $x top left x position of the text box
     * @param  int $x top left y position of the text box
     * @param  int $w width of the text box
     * @param  int $h height of the text box
     * @param  string $text Text to write in the box
     * @param  string $align Alignment (L, R, C)
     * @param  string|null $suffix Text suffix
     * @return void
     */
    public function cell_text(int $x, int $y, int $w, int $h, string $text, string $align = 'L', string $suffix = null)
    {
        $text = $this->encode($text.$suffix);
        $this->SetXY($x - $this->shiftX, $y - $this->shiftY);
        $this->Cell($w, $h, $text, 0, 0, $align, true);
    }

    /**
     * Encode text to write on PDF
     * 
     * @param  string $text Text to encode
     * @return string Encoded text
     */
    protected function encode(string $text){
        //setlocale(LC_ALL,'fr_FR.UTF-8');
        //$str = Str::ascii($str, 'fr');
        //return @iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        return @iconv('UTF-8', 'windows-1252', $text);
    }
}
