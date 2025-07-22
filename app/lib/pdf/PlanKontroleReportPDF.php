<?php
// app/lib/pdf/PlanKontroleReportPDF.php

require_once __DIR__ . '/EvidencijaReportPDF.php'; // Potrebno zbog BilingualCell metode

/**
 * Specijalizovana klasa za PDF izveštaje o Planovima Kontrole.
 */
class PlanKontroleReportPDF extends EvidencijaReportPDF {
    
    function TableHeader($header_sr, $header_en = []) {
        $this->SetFillColor(230, 230, 230);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        
        $y_start = $this->GetY();
        $height = empty($header_en) ? 7 : 12;

        for($i = 0; $i < count($header_sr); $i++) {
            $x = $this->GetX();
            $this->Rect($x, $y_start, $this->widths[$i], $height);
            $this->SetFont('DejaVu', 'B', 8);
            $this->SetTextColor(0);
            $this->SetXY($x, $y_start + 1.5);
            $this->MultiCell($this->widths[$i], 4, $header_sr[$i], 0, 'C');
            
            if (!empty($header_en) && isset($header_en[$i])) {
                $this->SetXY($x, $y_start + 6); 
                $this->SetFont('DejaVu', 'BI', 7);
                $this->SetTextColor(80, 80, 80);
                $this->MultiCell($this->widths[$i], 4, $header_en[$i], 0, 'C');
            }
            
            $this->SetXY($x + $this->widths[$i], $y_start);
            $this->SetTextColor(0);
        }
        $this->Ln($height);
    }

    function TableRow($data, $imagePath = null, $imageColIndex = -1) {
        $this->SetFont('DejaVu', '', 9);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.2);

        $textHeight = 0;
        for($i = 0; $i < count($data); $i++) { $textHeight = max($textHeight, $this->NbLines($this->widths[$i], (string)$data[$i])); }
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
    
    function CheckPageBreak($h) { 
        if ($this->GetY() + $h > $this->PageBreakTrigger) { 
            $this->AddPage($this->CurOrientation); 
            if (!empty($this->headerData)) { 
                $this->TableHeader($this->headerData['sr'], $this->headerData['en']); 
            } 
        } 
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