<?php

namespace App\Utilities;

use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;


class PDFFiller extends FPDI
{
    public $font_family = 'Arial';
    public $font_style = '';

    public $shiftX = 0;
    public $shiftY = 0;

    public $fill_r = 245;
    public $fill_g = 250;
    public $fill_b = 100;

    public function __construct($orientation, $unit, $format){
        
        parent::__construct($orientation, $unit, $format);
        
        $this->SetFillColor($this->fill_r, $this->fill_g, $this->fill_b);
        $this->SetFont('Helvetica', '');
        $this->SetTextColor(30, 30, 30);
        $this->SetFontSize(8);
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false);
    }

    public function setFontStyle($style){
        $this->font_style = $style;
        $this->updateFont();
    }

    public function resetFillColor(){
        $this->SetFillColor($this->fill_r, $this->fill_g, $this->fill_b);
    }

    public function updateFont(){
        $this->SetFont($this->font_family, $this->font_style);
    }

    function Footer()
    {
        $this->SetY(0);
    }

    public function cell_text($x, $y, $w, $h, $text, $align = 'L', $suffix = null){
        $text = $this->encode($text.$suffix);
        $this->SetXY($x - $this->shiftX, $y - $this->shiftY);
        $this->Cell($w, $h, $text, 0, 0, $align, true);
    }

    protected function encode($str){
        //setlocale(LC_ALL,'fr_FR.UTF-8');
        //$str = Str::ascii($str, 'fr');
        //return @iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        return @iconv('UTF-8', 'windows-1252', $str);
        return $str;
    }
}
