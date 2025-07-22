<?php
// app/lib/pdf/AppPDF.php
require_once __DIR__ . '/../fpdf/tfpdf.php';

class App_PDF extends tFPDF {
    public $ReportTitle = 'Izvestaj';
    public $ReportTitleEn = 'Report';
    protected $widths;
    protected $headerData;
    private $fontsAdded = []; // Polje za praćenje koji su fontovi već dodati

    /**
     * Pomoćna metoda koja proverava da li je font dodat i dodaje ga ako nije,
     * pre nego što pozove originalnu SetFont metodu.
     */
    private function _setFont($family, $style = '', $size = 0) {
        $fontkey = strtolower($family) . strtoupper($style);
        
        if (!isset($this->fontsAdded[$fontkey])) {
            $file = '';
            if ($style == '') {
                 $file = 'DejaVuSans.ttf';
            } else if ($style == 'B') {
                 $file = 'DejaVuSans-Bold.ttf';
            } else if ($style == 'I') {
                 $file = 'DejaVuSans-Oblique.ttf';
            } else if ($style == 'BI') {
                 $file = 'DejaVuSans-BoldOblique.ttf';
            }

            if ($file) {
                $this->AddFont('DejaVu', $style, $file, true);
                $this->fontsAdded[$fontkey] = true;
            }
        }
        $this->SetFont('DejaVu', $style, $size);
    }

    function SetWidths($w) { $this->widths = $w; }
    function SetHeaderData($header_sr, $header_en = []) { $this->headerData = ['sr' => $header_sr, 'en' => $header_en]; }

    function Header() {
        $logoPath = __DIR__ . '/../../../public/images/logo.png';
        if (file_exists($logoPath)) { $this->Image($logoPath, 10, 8, 40); }
        
        $this->SetY(15); 
        $this->_setFont('DejaVu', 'B', 16); 
        $this->SetTextColor(0);
        $this->Cell(45); 
        $this->Cell($this->GetPageWidth() - 90, 8, mb_strtoupper($this->ReportTitle, 'UTF-8'), 0, 1, 'C');
        
        if ($this->ReportTitleEn) {
            $this->_setFont('DejaVu', 'BI', 10); 
            $this->SetTextColor(80, 80, 80);
            $this->Cell(45); 
            $this->Cell($this->GetPageWidth() - 90, 8, mb_strtoupper($this->ReportTitleEn, 'UTF-8'), 0, 1, 'C');
        }
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15); 
        $this->_setFont('DejaVu', '', 8);
        $this->Cell(0, 10, 'Strana ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function BilingualCell($label_sr, $label_en, $value) {
        $this->_setFont('DejaVu', 'B', 10); $this->SetTextColor(0);
        $this->Cell(50, 5, $label_sr, 0, 0, 'L');
        $this->_setFont('DejaVu', '', 10);
        $this->MultiCell(0, 5, $value, 0, 'L');
        $this->_setFont('DejaVu', 'I', 8);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(50, 3, $label_en, 0, 0, 'L');
        $this->Ln(5); $this->SetTextColor(0);
    }
    
    function ChecklistRow($description, $result) {
        $y1 = $this->GetY();
        $this->_setFont('DejaVu', '', 10); $this->SetTextColor(0,0,0);
        $this->MultiCell(150, 5, $description, 0, 'L');
        $y2 = $this->GetY();
        $this->SetY($y1); $this->SetX(160);
        $this->_setFont('DejaVu', 'B', 10);
        $this->MultiCell(40, 5, $result, 0, 'R');
        $y3 = $this->GetY();
        $this->SetY(max($y2, $y3));
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        $this->Ln(2);
    }

    function TableHeader($header_sr, $header_en = []) {
        $this->SetFillColor(230,230,230); $this->SetTextColor(0); $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        
        $y_start = $this->GetY();
        $height = empty($header_en) ? 7 : 12;

        for($i = 0; $i < count($header_sr); $i++) {
            $x = $this->GetX();
            $this->Rect($x, $y_start, $this->widths[$i], $height);
            $this->_setFont('DejaVu', 'B', 8);
            $this->SetTextColor(0);
            $this->SetXY($x, $y_start + 1.5);
            $this->MultiCell($this->widths[$i], 4, $header_sr[$i], 0, 'C');
            
            if (!empty($header_en) && isset($header_en[$i])) {
                $this->SetXY($x, $y_start + 6); 
                $this->_setFont('DejaVu', 'BI', 7);
                $this->SetTextColor(80, 80, 80);
                $this->MultiCell($this->widths[$i], 4, $header_en[$i], 0, 'C');
            }
            
            $this->SetXY($x + $this->widths[$i], $y_start);
            $this->SetTextColor(0);
        }
        $this->Ln($height);
    }

    function TableRow($data, $imagePath = null, $imageColIndex = -1) {
        $this->_setFont('DejaVu', '', 9);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.2);

        $textHeight = 0;
        for($i=0; $i<count($data); $i++) { $textHeight = max($textHeight, $this->NbLines($this->widths[$i], (string)$data[$i])); }
        $h = 5 * $textHeight;
        
        $imageMaxHeight = 30;
        if ($imagePath) { $h = max($h, $imageMaxHeight); }
        
        $this->CheckPageBreak($h);
        
        $y_start = $this->GetY();
        $this->SetX($this->lMargin);

        for($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $x = $this->GetX();
            $align = ($i == 0 || $i == 4) ? 'C' : 'L';
            $cellTextLines = $this->NbLines($w, (string)$data[$i]);
            $cellTextHeight = 5 * $cellTextLines;
            $paddingTop = ($h - $cellTextHeight) / 2;
            $this->Rect($x, $y_start, $w, $h);
            $this->SetY($y_start + $paddingTop);
            $this->SetX($x);
            if ($i !== $imageColIndex) {
                $this->MultiCell($w, 5, (string)$data[$i], 0, $align);
            }
            $this->SetXY($x + $w, $y_start);
        }
        
        if ($imagePath && $imageColIndex >= 0) {
            $x_offset_for_image = $this->lMargin;
            for ($k=0; $k<$imageColIndex; $k++) { $x_offset_for_image += $this->widths[$k]; }
            $imageCellWidth = $this->widths[$imageColIndex];
            list($imgWidth, $imgHeight) = @getimagesize($imagePath);
            if ($imgWidth && $imgHeight) {
                $ratio = $imgWidth / $imgHeight;
                $newWidth = $imageCellWidth - 4;
                $newHeight = $newWidth / $ratio;
                if ($newHeight > $h - 4) { $newHeight = $h - 4; $newWidth = $newHeight * $ratio; }
                $this->Image($imagePath, $x_offset_for_image + ($imageCellWidth - $newWidth) / 2, $y_start + ($h - $newHeight) / 2, $newWidth, $newHeight);
            }
        }
        $this->Ln($h);
    }
    
    public function ShouldPageBreak($h) {
        return ($this->GetY() + $h) > $this->PageBreakTrigger;
    }
        
    function NbLines($w, $txt) { 
        if($w == 0) { return 1; } 
        $wmax = $w - 2 * $this->cMargin; 
        $s = str_replace("\r", '', $txt); 
        $lines = explode("\n", $s); 
        $nl = 0; 
        foreach($lines as $line){ 
            if (empty(trim($line)) && $line !== '0') { 
                $nl++; 
                continue; 
            } 
            $line_width = $this->GetStringWidth($line); 
            if ($line_width > 0) { 
                $nl += ceil($line_width / $wmax); 
            } else { 
                $nl++; 
            } 
        } 
        return $nl ?: 1; 
    }

}
?>