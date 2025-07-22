<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Evidencija.php';
require_once __DIR__ . '/../lib/pdf/EvidencijaReportPDF.php';
require_once __DIR__ . '/../lib/pdf/PlanKontroleReportPDF.php';

class ReportController {
    private $db;
    private $userModel;
    private $evidencijaModel;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->userModel = new User($this->db);
        $this->evidencijaModel = new Evidencija($this->db);
    }

    private function checkAdmin() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || $_SESSION['user_uloga'] !== 'administrator') {
            $_SESSION['error_message'] = 'Nemate dozvolu za pristup ovoj stranici.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }
    }

    public function showReportForm() {
        $this->checkAdmin();
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Generisanje Izveštaja');
        }
        $kontrolori = $this->userModel->getControllers();
        return ['kontrolori' => $kontrolori];
    }
    
     public function generateReport() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Neovlašćen pristup.'); }

        $filters = [
            'datum_od' => $_POST['datum_od'] ?? null,
            'datum_do' => $_POST['datum_do'] ?? null,
            'ident' => $_POST['ident'] ?? null,
            'kataloska_oznaka' => $_POST['kataloska_oznaka'] ?? null,
            'kontrolor_id' => $_POST['kontrolor_id'] ?? null,
        ];
        
        $records = $this->evidencijaModel->getRecordsForReport($filters);
        
        $pdf = new PlanKontroleReportPDF();
        
        // ===== ISPRAVKA: Dodavanje fontova odmah nakon kreiranja objekta =====
        $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        $pdf->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);
        $pdf->AddFont('DejaVu', 'BI', 'DejaVuSans-BoldOblique.ttf', true);
        
        $pdf->ReportTitle = 'IZVEŠTAJ O EVIDENCIJAMA';
        $pdf->ReportTitleEn = 'CONTROL RECORDS REPORT';
        $pdf->AliasNbPages();
        $pdf->AddPage('L', 'A4');
        
        if (empty($records)) {
            $pdf->SetFont('DejaVu', '', 12);
            $pdf->Cell(0, 10, 'Nema podataka za izabrane filtere.', 0, 1, 'C');
            $pdf->Output('I', 'izvestaj_prazan.pdf', true);
            exit;
        }
        
        $header_sr = ['ID', 'Kontrolor', 'Vrsta', 'Ident', 'Kat. oznaka', 'Naziv proizvoda', 'Ser. broj', 'Datum'];
        $header_en = ['ID', 'Controller', 'Type', 'Ident', 'Cat. number', 'Product Name', 'Serial No.', 'Date'];
        $pdf->SetWidths([10, 40, 35, 30, 30, 65, 30, 30]);
        $pdf->SetHeaderData($header_sr, $header_en);
        $pdf->TableHeader($header_sr, $header_en);
        
        foreach ($records as $record) {
            $rowData = [
                $record['id'],
                $record['kontrolor_puno_ime'],
                str_replace('_', ' ', ucfirst($record['vrsta_kontrole'])),
                $record['product_ident_sken'],
                $record['product_kataloska_oznaka_sken'],
                $record['product_naziv_sken'],
                $record['product_serijski_broj_sken'],
                date('d.m.Y H:i', strtotime($record['datum_vreme_ispitivanja']))
            ];
            $pdf->TableRow($rowData);
        }
        
        $fileName = 'izvestaj';
        if (!empty($filters['datum_od'])) { $fileName .= '_od_' . $filters['datum_od']; }
        if (!empty($filters['datum_do'])) { $fileName .= '_do_' . $filters['datum_do']; }
        $fileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileName) . '.pdf';
        
        $pdf->Output('D', $fileName, true);
    }
    
    public function generateSingleReport($id) {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) { exit('Nemate dozvolu.'); }
        $record = $this->evidencijaModel->getByIdWithDetails($id);
        if (!$record) { exit('Zapis nije pronađen.'); }
        
        $pdf = new EvidencijaReportPDF();
        
        // ===== ISPRAVKA: Dodavanje fontova odmah nakon kreiranja objekta =====
        $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        $pdf->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);
        $pdf->AddFont('DejaVu', 'BI', 'DejaVuSans-BoldOblique.ttf', true);
        
        $pdf->ReportTitle = 'IZVEŠTAJ O KONTROLI';
        $pdf->ReportTitleEn = 'CONTROL REPORT';
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('DejaVu', 'B', 12);
        
        $pdf->Cell(0, 8, 'Detalji Zapisa / Record Details', 0, 1, 'L', true);
        $pdf->Ln(2);
        $pdf->BilingualCell('ID Zapisa:', 'Record ID:', $record['id']);
        $pdf->BilingualCell('Datum i vreme:', 'Date and time:', date('d.m.Y H:i:s', strtotime($record['datum_vreme_ispitivanja'])));
        $pdf->BilingualCell('Kontrolor:', 'Controller:', $record['kontrolor_puno_ime']);
        $pdf->BilingualCell('Vrsta kontrole:', 'Control type:', str_replace('_', ' ', ucfirst($record['vrsta_kontrole'])));
        if (!empty($record['ime_kupca'])) {
            $pdf->BilingualCell('Ime Kupca:', 'Customer Name:', $record['ime_kupca']);
        }
        $pdf->Ln(3);
        
        $pdf->SetFont('DejaVu', 'B', 12);
        $pdf->Cell(0, 8, 'Podaci o Proizvodu / Product Data', 0, 1, 'L', true);
        $pdf->Ln(2);
        $pdf->BilingualCell('Naziv proizvoda:', 'Product Name:', $record['product_naziv_sken']);
        $pdf->BilingualCell('Ident:', 'Ident:', $record['product_ident_sken']);
        $pdf->BilingualCell('Kataloška oznaka:', 'Catalog number:', $record['product_kataloska_oznaka_sken']);
        $pdf->BilingualCell('Serijski broj:', 'Serial Number:', $record['product_serijski_broj_sken']);
        $pdf->Ln(3);
        
        $pdf->SetFont('DejaVu', 'B', 12);
        $pdf->Cell(0, 8, 'Rezultati Cek-Liste / Checklist Results', 0, 1, 'L', true);
        $pdf->Ln(2);
        
        if (!empty($record['rezultati'])) {
            $currentGroup = null;
            foreach($record['rezultati'] as $rezultat) {
                if ($rezultat['naziv_grupe'] !== $currentGroup) {
                    if ($currentGroup !== null) { $pdf->Ln(3); }
                    $pdf->SetFont('DejaVu', 'B', 11);
                    $pdf->SetFillColor(245, 245, 245);
                    $pdf->Cell(0, 7, $rezultat['naziv_grupe'], 0, 1, 'L', true);
                    $pdf->Ln(1);
                    $currentGroup = $rezultat['naziv_grupe'];
                }
                $rezultat_prikaz = $rezultat['rezultat_ok_nok'] ?? $rezultat['rezultat_tekst'];
                $opis = ($rezultat['redni_broj_karakteristike'] ?? '') . '. ' . $rezultat['opis_karakteristike_snapshot'];
                $pdf->ChecklistRow($opis, $rezultat_prikaz);
            }
        } else { $pdf->SetFont('DejaVu', '', 10); $pdf->Cell(0, 6, 'Nema rezultata.'); $pdf->Ln(); }
        $pdf->Ln(3);
        

        if (!empty($record['ostale_napomene'])) {
            $pdf->AddPage();
            $pdf->SetFont('DejaVu', 'B', 12);
            $pdf->Cell(0, 8, 'Ostale Napomene / Other Notes', 0, 1, 'L', true);
            $pdf->Ln(2);
            $pdf->SetFont('DejaVu', '', 10);
            $pdf->MultiCell(0, 6, $record['ostale_napomene']);
        }
      
        if (!empty($record['fotografije_masine'])) {
            $pdf->Ln(5);
            $pdf->SetFont('DejaVu', 'B', 12);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(0, 8, 'Fotografije Masine / Machine Photos', 0, 1, 'L', true);
            $pdf->Ln(2);

            $photo_width = 90;
            $margin = 5;
            $x_start = $pdf->GetX();
            $y_pos = $pdf->GetY();
            $col = 0;

            foreach($record['fotografije_masine'] as $foto) {
                $photo_path = UPLOADS_PATH . '/' . $foto['putanja_fotografije'];
                if(file_exists($photo_path)) {
                    
                    $current_x = $x_start + ($col * ($photo_width + $margin));
                    
                    list($img_w, $img_h) = getimagesize($photo_path);
                    $aspect_ratio = $img_h / $img_w;
                    $photo_height = $photo_width * $aspect_ratio;
                    
                    if ($pdf->ShouldPageBreak($photo_height)) {
                        $pdf->AddPage();
                        $pdf->SetFont('DejaVu', 'B', 12);
                        $pdf->Cell(0, 8, 'Fotografije Masine / Machine Photos (nastavak)', 0, 1, 'L', true);
                        $pdf->Ln(2);
                        $y_pos = $pdf->GetY();
                        $col = 0;
                    }
        

                    $pdf->Image($photo_path, $current_x, $y_pos, $photo_width);
                    
                    if ($col == 1) {
                        $y_pos += $photo_height + $margin;
                        $col = 0;
                        $pdf->SetY($y_pos);
                    } else {
                        $col++;
                    }
                }
            }
            $pdf->Ln();
        }
 
        $fileName = 'Izvestaj_ID' . $record['id'] . '_' . $record['product_ident_sken'] . '_SN' . $record['product_serijski_broj_sken'] . '.pdf';
        $fileName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileName);
        $pdf->Output('D', $fileName, true);
    }

    public function generatePlanReport($id) {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) { exit('Nemate dozvolu.'); }
        require_once __DIR__ . '/../models/PlanKontrole.php';
        $planKontroleModel = new PlanKontrole($this->db);
        $plan = $planKontroleModel->getPlanByIdWithDetails($id);
        if (!$plan) { exit('Plan nije pronađen.'); }
        
        $pdf = new PlanKontroleReportPDF();
        
        // ===== ISPRAVKA: Dodavanje fontova odmah nakon kreiranja objekta =====
        $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        $pdf->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);
        $pdf->AddFont('DejaVu', 'BI', 'DejaVuSans-BoldOblique.ttf', true);
        
        $pdf->ReportTitle = 'PLAN KONTROLE';
        $pdf->ReportTitleEn = 'CONTROL PLAN';
        $pdf->AliasNbPages();
        $pdf->AddPage('P', 'A4');
        
        $pdf->SetFont('DejaVu', 'B', 12);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(0, 8, 'Osnovni Podaci / Basic Data', 0, 1, 'L', true);
        $pdf->Ln(2);
        $pdf->BilingualCell('Broj Plana:', 'Control Plan No.:', $plan['broj_plana_kontrole']);
        $pdf->BilingualCell('Ident Proizvoda:', 'Product Ident:', $plan['ident_proizvoda']);
        $pdf->BilingualCell('Naziv Proizvoda:', 'Product Name:', $plan['naziv_proizvoda']);
        $pdf->BilingualCell('Kataloška Oznaka:', 'Catalog Number:', $plan['kataloska_oznaka']);
        $pdf->BilingualCell('Broj Operacije:', 'Operation No.:', $plan['broj_operacije']);
        $pdf->Ln(5);
        
        if (!empty($plan['grupe'])) {
            foreach ($plan['grupe'] as $grupa) {
                $pdf->SetFont('DejaVu', 'B', 12);
                $pdf->Cell(0, 8, $grupa['naziv_grupe'], 0, 1, 'L', true);
                if (!empty($grupa['karakteristike'])) {
                    $header_sr = ['R.br.', 'Opis karakteristike', 'Slika', 'Kontrolni alat', 'Vel. uzorka'];
                    $header_en = ['No.', 'Characteristic desc.', 'Image', 'Control tool/method', 'Sample size'];
                    $pdf->SetWidths([15, 65, 40, 45, 25]);
                    $pdf->SetHeaderData($header_sr, $header_en);
                    $pdf->TableHeader($header_sr, $header_en);
                    foreach ($grupa['karakteristike'] as $kar) {
                        $photo_path = !empty($kar['putanja_fotografije_opis']) && file_exists(UPLOADS_PATH . '/' . $kar['putanja_fotografije_opis']) ? UPLOADS_PATH . '/' . $kar['putanja_fotografije_opis'] : null;
                        $rowData = [$kar['redni_broj_karakteristike'], $kar['opis_karakteristike'], '', $kar['kontrolni_alat_nacin'], $kar['velicina_uzorka']];
                        $pdf->TableRow($rowData, $photo_path, 2);
                    }
                } else { $pdf->Cell(0, 6, 'Nema definisanih karakteristika za ovu grupu.'); $pdf->Ln(); }
                $pdf->Ln(5);
            }
        }
        
       $fileName = 'Plan_kontrole_' . $plan['broj_plana_kontrole'] . '_' . $plan['ident_proizvoda'] . '_' . $plan['kataloska_oznaka'] . '.pdf';
        $fileName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileName);
        $pdf->Output('D', $fileName, true);
    }
}
?>