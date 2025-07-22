<?php
// app/lib/pdf/EvidencijaReportPDF.php

require_once __DIR__ . '/../fpdf/tfpdf.php';

/**
 * Specijalizovana klasa za PDF izveštaje o evidencijama.
 */
class EvidencijaReportPDF extends tFPDF {
    public $ReportTitle = 'Izvestaj';
    public $ReportTitleEn = 'Report';
    protected $widths;
    protected $headerData;

    function SetWidths($w) { $this->widths = $w; }
    function SetHeaderData($header_sr, $header_en = []) { $this->headerData = ['sr' => $header_sr, 'en' => $header_en]; }

    function Header() {
        $logoPath = __DIR__ . '/../../../public/images/logo.png';
        if (file_exists($logoPath)) { $this->Image($logoPath, 10, 8, 40); }
        
        $this->SetY(15); 
        $this->SetFont('DejaVu', 'B', 16); 
        $this->SetTextColor(0);
        $this->Cell(45); 
        $this->Cell($this->GetPageWidth() - 90, 8, mb_strtoupper($this->ReportTitle, 'UTF-8'), 0, 1, 'C');
        
        if ($this->ReportTitleEn) {
            $this->SetFont('DejaVu', 'BI', 10); 
            $this->SetTextColor(80, 80, 80);
            $this->Cell(45); 
            $this->Cell($this->GetPageWidth() - 90, 8, mb_strtoupper($this->ReportTitleEn, 'UTF-8'), 0, 1, 'C');
        }
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15); 
        $this->SetFont('DejaVu', '', 8);
        $this->Cell(0, 10, 'Strana ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function BilingualCell($label_sr, $label_en, $value) {
        $this->SetFont('DejaVu', 'B', 10); 
        $this->SetTextColor(0);
        $this->Cell(50, 5, $label_sr, 0, 0, 'L');
        $this->SetFont('DejaVu', '', 10);
        $this->MultiCell(0, 5, $value, 0, 'L');
        $this->SetFont('DejaVu', 'I', 8);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(50, 3, $label_en, 0, 0, 'L');
        $this->Ln(5); 
        $this->SetTextColor(0);
    }
    
    // Metoda za ispis reda ček-liste
    function ChecklistRow($description, $result) {
        $y1 = $this->GetY();
        $this->SetFont('DejaVu', '', 10);
        $this->SetTextColor(0,0,0);
        $this->MultiCell(150, 5, $description, 0, 'L');
        $y2 = $this->GetY();
        $this->SetY($y1);
        $this->SetX(160); 
        $this->SetFont('DejaVu', 'B', 10);
        $this->MultiCell(40, 5, $result, 0, 'R');
        $y3 = $this->GetY();
        $this->SetY(max($y2, $y3));
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        $this->Ln(2);
    }
    
    public function ShouldPageBreak($h) {
        return ($this->GetY() + $h) > $this->PageBreakTrigger;
    }
}
?>